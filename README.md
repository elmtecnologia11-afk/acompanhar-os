# Acompanhar OS

Sistema de acompanhamento de Ordens de Servico conectado ao banco Firebird do SISComercio.

## Funcionalidades

- Dashboard com OS abertas por tipo de dispositivo
- Listagem de OS com filtros
- Detalhes da OS com timeline
- Atualizacao automatica a cada 30 segundos
- Cores por categoria (Computadores, Notebooks, Impressoras)

## Como usar

1. Instalar PHP com extensao `pdo_firebird`
2. Copiar a pasta do projeto
3. Editar `config.json` com o caminho do banco Firebird
4. Rode: `php -S 0.0.0.0:8081`

## Arquivos

- `config.json` - Configuracao do banco (editar este arquivo)
- `config.php` - Funcoes auxiliares
- `index.php` - Pagina inicial
- `os_list.php` - Listagem de OS
- `os_detalhe.php` - Detalhes da OS
- `style.css` - Estilos
- `iniciar.bat` - Iniciar servidor PHP (Windows)
