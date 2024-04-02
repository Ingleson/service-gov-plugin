<?php

/*
Plugin Name: Serviços de Orgãos do Governo
Description: Cria Posts utilizando api do Governo e uma lista dos orgãos do mesmo que são atualizados semanalmente.
Version: 1.3
Author: Ingleson
*/

function is_week_passed($last_execution_time) {
    $current_time = time();
    $one_week_seconds = 604800;

    if($current_time - $last_execution_time >= $one_week_seconds) {
        return true;
    } else {
        return false;
    }
}

function replace_link($description) {
    $pattern = '/\[(.*?)\]\((.*?)\)/';

    $description = preg_replace_callback($pattern, function($matches) {
        return "<a href='{$matches[2]}'>{$matches[1]}</a>";
    }, $description);

    return $description;
}

function perform_actions() {
    $lista_cod_siorgs = array(
        46,
        4243,
        46876,
        86144,
        21089,
        222120,
        45013,
    );
    $url_base = "https://www.servicos.gov.br/api/v1/servicos/orgao/";
    
    foreach ($lista_cod_siorgs as $cod_siorg) {
        $url_completa = $url_base . $cod_siorg;
    
        $resposta = wp_remote_get($url_completa);
    
        if (!is_wp_error($resposta) && wp_remote_retrieve_response_code($resposta) == 200) {
            $dados_servicos = json_decode(wp_remote_retrieve_body($resposta), true)["resposta"];
            
            foreach ($dados_servicos as $dados_servico) {

                $post_title = $dados_servico["nome"];
                $post_exists = get_page_by_title($post_title, OBJECT, 'post');
                if ($post_exists) {
                    continue;
                }
                
                $etapas = array();

                if (isset($dados_servico["etapas"]) && is_array($dados_servico["etapas"])) {
                    foreach ($dados_servico["etapas"] as $indice => $etapa) {

                        $documentos = isset($etapa['documentos']['documentos']) ? $etapa['documentos']['documentos'] : array();
                        $custos = isset($etapa['custos']['custos']) ? $etapa['custos']['custos'] : array();
                        
                        $canais_descricao = isset($etapa['canaisDePrestacao']['descricao']) ? $etapa['canaisDePrestacao']['descricao'] : '';
                        $canais_descricao = replace_link($canais_descricao);

                    
                        $etapas[] = array(
                            'titulo' => ($indice + 1) . '.' . $etapa["titulo"],
                            'descricao' => $etapa['descricao'] ? $etapa['descricao'] : '',
                            'canaisDePrestacao' => array(
                                'tipo'      => $etapa['canaisDePrestacao']['canaisDePrestacao'][0]['tipo'],
                                'descricao' => $canais_descricao,
                            ),
                            'documentos' => array(
                                'documentos' => $documentos
                            ),
                            'custos' => array(
                                'custos' => $custos
                            ),
                            'tempoTotalEstimado' => $etapa['tempoTotalEstimado']
                        );
                    }
                }

                $nome_categoria = $dados_servico['categoria']['nomeCategoria'];

                $categoria_id = get_cat_ID($nome_categoria);

                if ($categoria_id == 0) {
                    $categoria_id = wp_create_category($nome_categoria);
                }

                $post_id = wp_insert_post(array(
                    'post_title'   => $dados_servico["nome"],
                    'post_content' => $dados_servico["descricao"],
                    'post_status'  => 'publish',
                    'post_category'=> array($categoria_id),
                    'post_author'  => 3,
                    'meta_input'   => array(
                        'id'       => $dados_servico['id'],
                        'free'     => $dados_servico['gratuito'],
                        'sigla'    => $dados_servico['sigla'],
                        'contact'  => $dados_servico['contato'],
                        'description' => $dados_servico['descricao'],
                        'linkService' => $dados_servico['linkServicoDigital'],
                        'nameFirstCategory' => $dados_servico['categoria']['categoriaSuperior']['categoriaSuperior']['nomeCategoria'],
                        'nameSecondCategory' => $dados_servico['categoria']['categoriaSuperior']['nomeCategoria'],
                        'nameCurrentCategory'   => $dados_servico['categoria']['nomeCategoria'],
                        'percentPositiveAva' => $dados_servico['percentualAvaliacoesPositivas'],
                        'positiveAva' => $dados_servico['avaliacoes']['positivas'],
                        'negativeAva' => $dados_servico['avaliacoes']['negativas'],
                        'requester' => $dados_servico['solicitantes']['solicitante'][0]['tipo'],
                        'etapas' => $etapas,
                        'tempoTotalEstimado' => $dados_servico['tempoTotalEstimado'],
                        'nameOrgan' => $dados_servico['orgao']['nomeOrgao'],
                        'linkOrgan' => $dados_servico['orgao']['id'],
                        'noRequireTreatment' => $dados_servico['tratamentoDispensadoAtendimento'],
                        'priorityTreatment' => $dados_servico['tratamentoPrioritario'],
                        'acessibilityCondition' => $dados_servico['condicoesAcessibilidade'],
                    )
                    
                ));

                $meta_name_first_category = get_post_meta($post_id, 'nameFirstCategory', true);
                $meta_name_second_category = get_post_meta($post_id, 'nameSecondCategory', true);
                $meta_name_current_category = get_post_meta($post_id, 'nameCurrentCategory', true);
                
                $meta_negative_ava = get_post_meta($post_id, 'negativeAva', true);
                $meta_positive_ava = get_post_meta($post_id, 'positiveAva', true);

                $meta_all_time = get_post_meta($post_id, 'tempoTotalEstimado', true);

                $meta_free = get_post_meta($post_id, 'free', true);

                $data_atual = date('d/m/Y');
                $meta_all_available = $meta_negative_ava + $meta_positive_ava;
                $categories = "$meta_name_first_category\n $meta_name_second_category > $meta_name_current_category";
                $is_free = '';
                $meta_sigla = get_post_meta($post_id, 'sigla', true);
                $meta_contato = get_post_meta($post_id, 'contato', true);
                $meta_percentual = get_post_meta($post_id, 'percentPositiveAva', true);
                $meta_description = get_post_meta($post_id, 'description', true);
                $meta_requester = get_post_meta($post_id, 'requester', true);
                $meta_all_time_max = $meta_all_time['entre']['max'];
                $meta_all_time_uni = $meta_all_time['entre']['unidade'];
                $meta_name_organ = get_post_meta($post_id, 'nameOrgan', true);
                $meta_link_organ = get_post_meta($post_id, 'linkOrgan', true);
                $no_require_treatment = get_post_meta($post_id, 'noRequireTreatment', true);
                $priority_treatment = get_post_meta($post_id, 'priorityTreatment', true);
                $acessibility_condition = get_post_meta($post_id, 'acessibilityCondition', true);

                $etapas_content = '';

                foreach ($etapas as $etapa) {
                    $canais_descricao = isset($etapa['canaisDePrestacao']['descricao']) ? $etapa['canaisDePrestacao']['descricao'] : '';
                    $tipo = isset($etapa['canaisDePrestacao']['tipo']) ? $etapa['canaisDePrestacao']['tipo'] : '';
                    $documentos = isset($etapa['documentos']['documentos']) ? $etapa['documentos']['documentos'] : '';
                    $custos = isset($etapa['custos']['custos']) ? $etapa['custos']['custos'] : '';

                    $documentos_content = '';
                    $custos_content = '';

                    foreach ($documentos as $documento) {
                        $current_document = $documento['nome'];
                        $documentos_content .= "<li>$current_document</li>";
                    }
                    foreach ($custos as $custo) {
                        $description_cost = $custo['descricao'];
                        $type_cost = $custo['moeda'];
                        $value_cost = $custo['valor'];
                        $custos_content .= "<li>$description_cost - $type_cost $value_cost</li>";
                    }
                
                    $etapas_content .= sprintf(
                        "<div class='etapa'>
                            <h5>%s</h5>
                            <p>%s</p>
                            <h5>CANAIS DE PRESTAÇÃO</h5>
                            <ul>
                                <li>Tipo: %s</li>
                                <li>Descrição: %s</li>
                            </ul>
                            <h5>DOCUMENTAÇÃO</h5>
                            <ul>%s</ul>
                            <h5>CUSTOS</h5>
                            <ul>%s</ul>
                            <h5>TEMPO DE DURAÇÃO DA ETAPA</h5>
                            <p>%s %s</p>
                        </div>",
                        $etapa['titulo'],
                        $etapa['descricao'],
                        $tipo,
                        $canais_descricao,
                        $documentos_content,
                        $custos_content,
                        $etapa['tempoTotalEstimado']['ate']['max'],
                        $etapa['tempoTotalEstimado']['ate']['unidade']
                    );
                }

                if(strlen($meta_sigla
                ) < 1) {
                    $meta_sigla = '';
                }
                if(strlen($meta_contato) <1) {
                    $meta_contato = '';
                }
                if($meta_all_available == 0) {
                    $meta_percentual = '';
                }
                if($meta_all_time_max == null) {
                    $meta_all_time_max = '';
                    $meta_all_time_uni = '';
                }
                if($meta_free == true) {
                    $is_free = 'Este serviço é gratuito para o cidadão.';
                }
                
                $post_content = "
                    <div class='all-content'>
                        <h3 class='all-categories'>$categories</h3>
                        <span class='acronym'>$meta_sigla</span>
                        <span class='percent'>Avaliações: $meta_percentual% ($meta_all_available)</span>
                        <span class='last-modify'>Última Modificação: $data_atual</span>

                        <h4 class='sub'>O que é</h4>
                        <p class='meta-desc'>$meta_description</p>

                        <h4 class='sub'>Quem pode utilizar este serviço?</h4>
                        <p class='meta-req'>$meta_requester</p>

                        <h4 class='sub'>Etapas para a realização deste serviço</h4>
                        <div class='stages'>$etapas_content</div>

                        <h4>Outras Informações</h4>
                        <h5>Quanto tempo leva?</h5>
                        <p class='meta-max-uni'>$meta_all_time_max $meta_all_time_uni</p>

                        <h5>Informações adicionais ao tempo estimado</h5>
                        <p class='offline'>Caso o serviço esteja fora de ar, o cidadão poderá contatar diretamente a área para saber sobre as etapas de sua solicitação</p>

                        <p class='is-free'>$is_free</p>

                        <h5>Para mais informações ou dúvidas sobre este serviço, entre em contato</h5>
                        <p class='meta-contact'>$meta_contato</p>

                        <p class='meta-name-link'>Este é um serviço do(a) $meta_name_organ. Em caso de duvidas, reclamações ou sugestões, favor, contactá-lo: $meta_link_organ</p>

                        <h5>Tratamento a ser dispensado ao usuário no atendimento</h5>
                        <p class='meta-require'>$no_require_treatment</p>

                        <h5>Informações sobre as condições de acessibilidade, sinalização, limpeza e conforto dos locais de atendimento</h5>
                        <p class='meta-acess'>$acessibility_condition</p>

                        <h5>Informação sobre quem tem direito a tratamento prioritário</h5>
                        <p class='meta-priority'>$priority_treatment</p>

                    </div>
                ";
                wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));
            }
        }
    }
}

$last_execution_time = get_option('last_execution_time');

if(!$last_execution_time || is_week_passed($last_execution_time)) {
    perform_actions();

    update_option('last_execution_time', time());
}