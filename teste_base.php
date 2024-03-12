<?php

register_activation_hook(__FILE__, 'ativacao');

/*
Plugin Name: Serviços de Orgãos do Governo
Description: Cria Posts utilizando api do Governo e uma lista dos orgãos do mesmo.
Version: Beta
Author: Ingleson
*/

function ativacao() {
    $lista_cod_siorgs = array(
        432,
    );
    $url_base = "https://www.servicos.gov.br/api/v1/servicos/orgao/";
    
    foreach ($lista_cod_siorgs as $cod_siorg) {
        $url_completa = $url_base . $cod_siorg;
    
        $resposta = wp_remote_get($url_completa);
    
        if (!is_wp_error($resposta) && wp_remote_retrieve_response_code($resposta) == 200) {
            $dados_servicos = json_decode(wp_remote_retrieve_body($resposta), true)["resposta"];

            
            foreach ($dados_servicos as $dados_servico) {
                
                $etapas = array();

                if (isset($dados_servico["etapas"]) && is_array($dados_servico["etapas"])) {
                    foreach ($dados_servico["etapas"] as $indice => $etapa) {
                        $canal_descricao = isset($etapa['canaisDePrestacao']['canaisDePrestacao'][0]['descricao']) ? $etapa['canaisDePrestacao']['canaisDePrestacao'][0]['descricao'] : '';

                        $documentos = isset($etapa['documentos']['documentos']) ? $etapa['documentos']['documentos'] : array();
                        $custos = isset($etapa['custos']['custos']) ? $etapa['custos']['custos'] : array();

                        $etapas[] = array(
                            'titulo' => ($indice + 1) . '.' . $etapa["titulo"],
                            'descricao' => $etapa["descricao"],
                            'canaisDePrestacao' => array(
                                'descricao' => $canal_descricao
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
                    'meta_input'   => array(
                        'id'       => $dados_servico['id'],
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

                $data_atual = date('d/m/Y');
                $meta_all_available = $meta_negative_ava + $meta_positive_ava;
                $categories = "$meta_name_first_category\n $meta_name_second_category > $meta_name_current_category";
                $meta_sigla = get_post_meta($post_id, 'sigla', true);
                $meta_contato = get_post_meta($post_id, 'contato', true);
                $meta_percentual = get_post_meta($post_id, 'percentPositiveAva', true);
                $meta_description = get_post_meta($post_id, 'description', true);
                $meta_requester = get_post_meta($post_id, 'requester', true);
                $meta_all_time_max = $meta_all_time['ate']['max'];
                $meta_all_time_uni = $meta_all_time['ate']['unidade'];
                $meta_name_organ = get_post_meta($post_id, 'nameOrgan', true);
                $meta_link_organ = get_post_meta($post_id, 'linkOrgan', true);
                $no_require_treatment = get_post_meta($post_id, 'noRequireTreatment', true);
                $priority_treatment = get_post_meta($post_id, 'priorityTreatment', true);
                $acessibility_condition = get_post_meta($post_id, 'acessibilityCondition', true);

                $etapas_content = '';

                foreach ($etapas as $etapa) {
                    $canais_descricao = isset($etapa['canaisDePrestacao']['descricao']) ? $etapa['canaisDePrestacao']['descricao'] : '';
                    $documentos = isset($etapa['documentos']['documentos']) ? $etapa['documentos']['documentos'] : array();
                    $custos = isset($etapa['custos']['custos']) ? $etapa['custos']['custos'] : array();
                
                    $etapas_content .= sprintf(
                        "%s\n%s\nCANAIS DE PRESTAÇÃO\n\n   %s\n\nDOCUMENTAÇÃO\n\n   %s\n\nCUSTOS\n\n   %s\nTEMPO DE DURAÇÃO DA ETAPA\n\n%s %s\n\n",
                        $etapa['titulo'],
                        $etapa['descricao'],
                        $canais_descricao,
                        implode("\n", $documentos),
                        implode("\n", $custos),
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
                
                $post_content = "
                    $categories\n
                    $meta_sigla
                    Avaliações: $meta_percentual% ($meta_all_available)
                    Última Modificação: $data_atual\n
                    O que é?\n
                    $meta_description\n
                    Quem pode utilizar este serviço?\n
                    $meta_requester\n
                    Etapas para realização deste serviço\n
                    $etapas_content\n
                    Outras Informações\n
                    Quanto tempo leva?
                    $meta_all_time_max $meta_all_time_uni\n
                    Informações adicionais ao tempo estimado\n
                    Para mais informações ou dúvidas sobre este serviço, entre em contato
                    $meta_contato\n
                    Este é um serviço do(a) $meta_name_organ. Em caso de duvidas, reclamações ou sugestões, favor, contactá-lo: $meta_link_organ\n
                    Tratamento a ser dispensado ao usuário no atendimento
                    $no_require_treatment\n
                    Informações sobre as condições de acessibilidade, sinalização, limpeza e conforto dos locais de atendimento
                    $acessibility_condition\n
                    Informação sobre quem tem direito a tratamento prioritário
                    $priority_treatment\n
                ";
                wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));
            }
        }
    }
}
