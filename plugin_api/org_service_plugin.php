<?php

/*
Plugin Name: Serviços de Orgãos do Governo
Description: Cria Posts utilizando api do Governo e uma lista dos orgãos do mesmo.
Version: 1.0
Author: Ingleson
*/

  $lista_cod_siorgs = array(
    432,
    458,
    454,
  );
  $url_base = "https://www.servicos.gov.br/api/v1/servicos/orgao/";

  foreach ($lista_cod_siorgs as $cod_siorg) {
    $url_completa = $url_base . $cod_siorg;

    $resposta = wp_remote_get($url_completa);

    if (!is_wp_error($resposta) && wp_remote_retrieve_response_code($resposta) == 200) {
      $dados_servico = json_decode(wp_remote_retrieve_body($resposta), true)["resposta"][0];

      // $etapas = array();
      // foreach ($dados_servico["etapas"] as $etapa) {
      //     $etapas[] = array(
      //         'titulo' => $etapa["titulo"],
      //         'descricao' => $etapa["descricao"],
      //         'canaisDePrestacao' => array(
      //             'descricao' => $etapa["canaisDePrestacao"]["canaisDePrestacao"][0]["descricao"]
      //         )
      //     );
      // }

      $post_id = wp_insert_post(array(
        'post_title'   => $dados_servico["nome"],
        'post_content' => $dados_servico["descricao"],
        'post_status'  => 'publish',
        'category'     => $dados_servico['categoria']['nomeCategoria'],
        'meta_input'   => array(
          'id'                            => $dados_servico["id"],
          'sigla'                         => $dados_servico["sigla"],
          'contato'                       => $dados_servico["contato"],
          'gratuito'                      => $dados_servico["gratuito"],
          'porcentagemDigital'            => $dados_servico["porcentagemDigital"],
          'servicoDigital'                => $dados_servico["servicoDigital"],
          'linkServicoDigital'            => $dados_servico["linkServicoDigital"],
          'solicitante'                   => $dados_servico["solicitantes"]["solicitante"][0],
          'validadeDocumento'             => $dados_servico["validadeDocumento"]["tipo"],
          'avaliacoes_positivas'          => $dados_servico["avaliacoes"]["positivas"],
          'avaliacoes_negativas'          => $dados_servico["avaliacoes"]["negativas"],
          'categoria_id'                  => $dados_servico["categoria"]["id"],
          'categoria_nome'                => $dados_servico["categoria"]["nomeCategoria"],
          'categoria_descricao'           => $dados_servico["categoria"]["descricaoCategoria"],
          'categoria_superior_id_1'       => $dados_servico["categoria"]["categoriaSuperior"]["id"],
          'categoria_superior_nome_1'     => $dados_servico["categoria"]["categoriaSuperior"]["nomeCategoria"],
          'categoria_superior_id_2'       => $dados_servico["categoria"]["categoriaSuperior"]["CategoriaSuperior"]["id"],
          'categoria_superior_nome_2'     => $dados_servico["categoria"]["categoriaSuperior"]["CategoriaSuperior"]["nomeCategoria"],
          'condicoesAcessibilidade'       => $dados_servico["condicoesAcessibilidade"],
          'tratamentoPrioritario'         => $dados_servico["tratamentoPrioritario"],
          'tratamentoDispensadoAtendimento' => $dados_servico["tratamentoDispensadoAtendimento"],
          // 'etapas'                        => $etapas,
          'percentualAvaliacoesPositivas' => $dados_servico["percentualAvaliacoesPositivas"] . "%"
        ),
      ));
    }
  }

?>