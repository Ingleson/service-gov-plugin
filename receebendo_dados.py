import requests

lista_cod_siorgs = [432]

url_base = "https://www.servicos.gov.br/api/v1/servicos/orgao/"

for cod_siorg in lista_cod_siorgs:
  url_completa = f"{url_base}{cod_siorg}"

  resposta = requests.get(url_completa)

  data = resposta.json()

  print(data)