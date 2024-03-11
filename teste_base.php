<?php

register_activation_hook(__FILE__, 'ativacao');

/*
Plugin Name: Serviços de Orgãos do Governo
Description: Cria Posts utilizando api do Governo e uma lista dos orgãos do mesmo.
Version: 1.0
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
                $post_id = wp_insert_post(array(
                    'post_title'   => $dados_servico["nome"],
                    'post_content' => $dados_servico["descricao"],
                    'post_status'  => 'publish',
                    // 'category'     => $dados_servico['categoria']['nomeCategoria'],
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
                        'requester' => $dados_servico['solicitantes']['solicitante'][0]['tipo']
                    )
                    
                ));

                $meta_name_first_category = get_post_meta($post_id, 'nameFirstCategory', true);
                $meta_name_second_category = get_post_meta($post_id, 'nameSecondCategory', true);
                $meta_name_current_category = get_post_meta($post_id, 'nameCurrentCategory', true);
                
                $meta_negative_ava = get_post_meta($post_id, 'negativeAva', true);
                $meta_positive_ava = get_post_meta($post_id, 'positiveAva', true);


                $data_atual = date('d/m/Y');
                $meta_all_available = $meta_negative_ava + $meta_positive_ava;
                $categories = "$meta_name_first_category\n $meta_name_second_category > $meta_name_current_category";
                // $meta_id = get_post_meta($post_id, 'id', true);
                $meta_sigla = get_post_meta($post_id, 'sigla', true);
                $meta_contato = get_post_meta($post_id, 'contato', true);
                $meta_link_servico = get_post_meta($post_id, 'linkService', true);
                $meta_percentual = get_post_meta($post_id, 'percentPositiveAva', true);
                $meta_description = get_post_meta($post_id, 'description', true);
                $meta_requester = get_post_meta($post_id, 'requester', true);


                if(strlen($meta_sigla
                ) < 1) {
                    $meta_sigla = 'Sem Sigla';
                }
                if(strlen($meta_contato) <1) {
                    $meta_contato = 'Sem Contato';
                }
                
                // $categories\n 

                $post_content = "
                    $meta_sigla\n 
                    Avaliações positivas: $meta_percentual% . ($meta_all_available)\n
                    Última Modificação: $data_atual\n
                    O que é?\n
                    $meta_description\n
                    Quem pode utilizar este serviço?\n
                    $meta_requester
                    Contato: $meta_contato\n 
                    Link do Serviço: $meta_link_servico
                ";
                wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));
            }
        }
    }
}
