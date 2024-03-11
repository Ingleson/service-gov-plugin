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

        echo '<pre>';
        print_r($dados_servico);
        echo '</pre>';
    }
}

// Agora, você pode realizar operações adicionais após o loop, sem interferir nos cabeçalhos.
// Por exemplo, você pode armazenar os dados em variáveis ou criar posts com base nos dados.
?>