<?php
/*
Plugin Name: Serviços de Orgãos do Governo
Description: Cria Posts utilizando API do Governo e uma lista dos órgãos do mesmo.
Version: 1.0
Author: Ingleson
*/

// Função para criar os posts
function criar_posts_servicos_governo() {
    $lista_cod_siorgs = array(432);
    $url_base = "https://www.servicos.gov.br/api/v1/servicos/orgao/";

    foreach ($lista_cod_siorgs as $cod_siorg) {
        $url_completa = $url_base . $cod_siorg;

        $resposta = wp_remote_get($url_completa);

        if (!is_wp_error($resposta) && wp_remote_retrieve_response_code($resposta) == 200) {
            $dados_servico = json_decode(wp_remote_retrieve_body($resposta), true);

            $post_title = isset($dados_servico["nome"]) ? $dados_servico["nome"] : '';
            // Verifica se o post já existe
            $existing_post = get_page_by_title($post_title, OBJECT, 'post');

            if (!$existing_post) {
                // Restante do seu código...

                // Certifique-se de verificar a existência das chaves antes de acessá-las
                $post_id = wp_insert_post(array(
                    'post_title'   => isset($dados_servico["nome"]) ? $dados_servico["nome"] : '',
                    'post_content' => isset($dados_servico["descricao"]) ? $dados_servico["descricao"] : '',
                    'post_status'  => 'publish',
                    'category'     => isset($dados_servico['categoria']['nomeCategoria']) ? $dados_servico['categoria']['nomeCategoria'] : '',
                    'meta_input'   => array(
                        'id'                            => isset($dados_servico["id"]) ? $dados_servico["id"] : '',
                        'sigla'                         => isset($dados_servico["sigla"]) ? $dados_servico["sigla"] : '',
                        'contato'                       => isset($dados_servico["contato"]) ? $dados_servico["contato"] : '',
                        'gratuito'                      => isset($dados_servico["gratuito"]) ? $dados_servico["gratuito"] : '',
                        'porcentagemDigital'            => isset($dados_servico["porcentagemDigital"]) ? $dados_servico["porcentagemDigital"] : '',
                        'servicoDigital'                => isset($dados_servico["servicoDigital"]) ? $dados_servico["servicoDigital"] : '',
                        'linkServicoDigital'            => isset($dados_servico["linkServicoDigital"]) ? $dados_servico["linkServicoDigital"] : '',
                        'solicitante'                   => isset($dados_servico["solicitantes"]["solicitante"][0]) ? $dados_servico["solicitantes"]["solicitante"][0] : '',
                        'validadeDocumento'             => isset($dados_servico["validadeDocumento"]["tipo"]) ? $dados_servico["validadeDocumento"]["tipo"] : '',
                        'avaliacoes_positivas'          => isset($dados_servico["avaliacoes"]["positivas"]) ? $dados_servico["avaliacoes"]["positivas"] : '',
                        'avaliacoes_negativas'          => isset($dados_servico["avaliacoes"]["negativas"]) ? $dados_servico["avaliacoes"]["negativas"] : '',
                        'categoria_id'                  => isset($dados_servico["categoria"]["id"]) ? $dados_servico["categoria"]["id"] : '',
                        'categoria_nome'                => isset($dados_servico["categoria"]["nomeCategoria"]) ? $dados_servico["categoria"]["nomeCategoria"] : '',
                        'categoria_descricao'           => isset($dados_servico["categoria"]["descricaoCategoria"]) ? $dados_servico["categoria"]["descricaoCategoria"] : '',
                        'categoria_superior_id_1'       => isset($dados_servico["categoria"]["categoriaSuperior"]["id"]) ? $dados_servico["categoria"]["categoriaSuperior"]["id"] : '',
                        'categoria_superior_nome_1'     => isset($dados_servico["categoria"]["categoriaSuperior"]["nomeCategoria"]) ? $dados_servico["categoria"]["categoriaSuperior"]["nomeCategoria"] : '',
                        'categoria_superior_id_2'       => isset($dados_servico["categoria"]["categoriaSuperior"]["CategoriaSuperior"]["id"]) ? $dados_servico["categoria"]["categoriaSuperior"]["CategoriaSuperior"]["id"] : '',
                        'categoria_superior_nome_2'     => isset($dados_servico["categoria"]["categoriaSuperior"]["CategoriaSuperior"]["nomeCategoria"]) ? $dados_servico["categoria"]["categoriaSuperior"]["CategoriaSuperior"]["nomeCategoria"] : '',
                        'condicoesAcessibilidade'       => isset($dados_servico["condicoesAcessibilidade"]) ? $dados_servico["condicoesAcessibilidade"] : '',
                        'tratamentoPrioritario'         => isset($dados_servico["tratamentoPrioritario"]) ? $dados_servico["tratamentoPrioritario"] : '',
                        'tratamentoDispensadoAtendimento' => isset($dados_servico["tratamentoDispensadoAtendimento"]) ? $dados_servico["tratamentoDispensadoAtendimento"] : '',
                        'percentualAvaliacoesPositivas' => isset($dados_servico["percentualAvaliacoesPositivas"]) ? $dados_servico["percentualAvaliacoesPositivas"] . "%" : '',
                        // Adicione outras chaves conforme necessário...
                    ),
                ));

                if (is_wp_error($post_id)) {
                    error_log('Erro ao criar post: ' . $post_id->get_error_message());
                } else {
                    error_log('Post criado com sucesso. ID: ' . $post_id);
                }
            } else {
                error_log('Post já existe com o ID: ' . $existing_post->ID);
            }
        } else {
            error_log('Erro ao buscar dados da API');
        }
    }
}

// Adiciona um gancho para executar a função após o WordPress ser completamente carregado
add_action('init', 'criar_posts_servicos_governo');