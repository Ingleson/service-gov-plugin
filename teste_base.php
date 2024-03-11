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
                        'linkServico' => $dados_servico['linkServicoDigital'],
                        'nameFirstCategory' => $dados_servico['categoria']['categoriaSuperior']['categoriaSuperior']['nomeCategoria'],
                        'nameSecondCategory' => $dados_servico['categoria']['categoriaSuperior']['nomeCategoria'],
                        'nameCurrentCategory'   => $dados_servico['categoria']['nomeCategoria']
                    )
                    
                ));

                $meta_name_first_category = get_post_meta($post_id, 'nameFirstCategory', true);
                $meta_name_second_category = get_post_meta($post_id, 'nameSecondCategory', true);
                $meta_name_current_category = get_post_meta($post_id, 'nameCurrentCategory', true);
                $meta_id = get_post_meta($post_id, 'id', true);
                $meta_sigla = get_post_meta($post_id, 'sigla', true);
                $meta_contato = get_post_meta($post_id, 'contato', true);
                $meta_link_servico = get_post_meta($post_id, 'linkServico', true);

                $post_title = "$meta_name_first_category\n $meta_name_second_category > $meta_name_current_category";
                $post_content = "ID: $meta_id\nSigla: $meta_sigla\nContato: $meta_contato\nLink do Serviço: $meta_link_servico";
                wp_update_post(array('ID' => $post_id, 'post_content' => $post_content, 'post_title' => $post_title));
            }
        }
    }
}
