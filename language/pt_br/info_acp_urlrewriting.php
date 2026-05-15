<?php
/**
 *
 * Advanced URL Rewriting extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 _Vinny_ <https://github.com/vinny>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_URLREWRITING_TITLE'                => 'URL Rewriting',
	'ACP_URLREWRITING'                      => 'URL Rewriting',
	'ACP_URLREWRITING_SETTINGS'             => 'Configurações',
	'ACP_URLREWRITING_SERVER'               => 'Configuração do servidor',
	'ACP_URLREWRITING_FAQ'                  => 'FAQ e guias',

	'VINNY_URL_REWRITE_ENABLE'              => 'Ativar URL Rewriting (Human-Friendly)',
	'VINNY_URL_SITEMAP_ENABLE'              => 'Ativar Sitemap XML',
	'VINNY_URL_OPENGRAPH_ENABLE'            => 'Ativar Open Graph Tags',
	'VINNY_URL_TRANSLIT_ENABLE'             => 'Ativar Transliteração (Remover acentos)',
	'VINNY_URL_REDIRECT_ENABLE'             => 'Ativar Redirecionamentos 301',

	'ACP_URLREWRITING_SITEMAP'              => 'Configurações do Sitemap',
	'ACP_URLREWRITING_SITEMAP_EXPLAIN'      => 'Configure as opções de geração do Sitemap XML.',

	'VINNY_URL_SITEMAP_CACHE_TIME'          => 'Tempo de Cache (Horas)',
	'VINNY_URL_SITEMAP_CACHE_TIME_EXPLAIN'  => 'Tempo em horas para manter o sitemap em cache. Defina como 0 para desativar.',
	'VINNY_URL_SITEMAP_LIMIT'               => 'Limite de URLs',
	'VINNY_URL_SITEMAP_LIMIT_EXPLAIN'       => 'Número máximo de URLs no sitemap (Limite do protocolo é 50000).',
	'VINNY_URL_SITEMAP_EXCLUDED'            => 'Fóruns Excluídos',
	'VINNY_URL_SITEMAP_EXCLUDED_EXPLAIN'    => 'Selecione os fóruns que devem ser excluídos do sitemap. Use CTRL+Clique para selecionar múltiplos.',
	'VINNY_URL_SITEMAP_PRIORITY'            => 'Prioridade',
	'VINNY_URL_SITEMAP_PRIORITY_EXPLAIN'    => 'A prioridade desta URL em relação a outras no seu site.',
	'VINNY_URL_SITEMAP_CHANGEFREQ'          => 'Frequência de Mudança',
	'VINNY_URL_SITEMAP_CHANGEFREQ_EXPLAIN'  => 'Com que frequência a página provavelmente mudará.',
	'VINNY_URL_CHANGEFREQ_ALWAYS'           => 'Sempre',
	'VINNY_URL_CHANGEFREQ_HOURLY'           => 'A cada hora',
	'VINNY_URL_CHANGEFREQ_DAILY'            => 'Diariamente',
	'VINNY_URL_CHANGEFREQ_WEEKLY'           => 'Semanalmente',
	'VINNY_URL_CHANGEFREQ_MONTHLY'          => 'Mensalmente',
	'VINNY_URL_CHANGEFREQ_YEARLY'           => 'Anualmente',
	'VINNY_URL_CHANGEFREQ_NEVER'            => 'Nunca',

	'ACP_URLREWRITING_SETTINGS_EXPLAIN'     => 'Configure as opções gerais do URL Rewriting. Antes de alterar qualquer opção abaixo, a leitura do FAQ é essencial para compreender o uso da extensão. Leia atentamente primeiro.',
	'ACP_URLREWRITING_SERVER_EXPLAIN'       => 'Copie as regras de reescrita para o seu servidor. Esta página não lê nem grava arquivos de configuração do servidor.',
	'ACP_URLREWRITING_FAQ_EXPLAIN'          => 'Guias de uso, configuração, funcionamento dos recursos e cuidados antes de desativar a extensão.',

	// Explanations for Settings
	'VINNY_URL_REWRITE_ENABLE_EXPLAIN'      => 'Ativa ou desativa a reescrita de URLs para o formato amigável. <b>IMPORTANTE: isso requer regras de reescrita no servidor.</b> Leia o FAQ para mais informações.',
	'VINNY_URL_SITEMAP_ENABLE_EXPLAIN'      => 'Ativa ou desativa a geração do Sitemap XML.',
	'VINNY_URL_OPENGRAPH_ENABLE_EXPLAIN'    => 'Adiciona meta tags Open Graph para melhor compartilhamento em redes sociais.',
	'VINNY_URL_TRANSLIT_ENABLE_EXPLAIN'     => 'Remove acentos e caracteres especiais das URLs (ex: "ação" vira "acao").',
	'VINNY_URL_REDIRECT_ENABLE_EXPLAIN'     => 'Redireciona URLs antigas para as novas URLs amigáveis via Redirecionamento 301.',

	'VINNY_URL_REWRITE_MODE'                => 'Modo de Reescrita de URL',
	'VINNY_URL_REWRITE_MODE_EXPLAIN'        => 'Selecione o formato das URLs amigáveis.',
	'VINNY_URL_MODE_SIMPLE'                 => 'Simples (ex: forum-f123, topico-t456)',
	'VINNY_URL_MODE_ADVANCED'               => 'Avançado (ex: nome-do-forum-f123, titulo-do-topico-t456)',

	'ACP_URLREWRITING_HTACCESS_IMPORTANT'   => 'Configuração Apache .htaccess',
	'ACP_URLREWRITING_HTACCESS_RULE_EXPLAIN'=> 'Copie e cole o código abaixo no arquivo .htaccess, depois da linha <code>RewriteEngine On</code>:',
	'ACP_URLREWRITING_NGINX_IMPORTANT'      => 'Configuração NGINX',
	'ACP_URLREWRITING_NGINX_RULE_EXPLAIN'   => 'Adicione as regras abaixo ao bloco de servidor do NGINX:',
	'ACP_URLREWRITING_SELECT_SERVER'        => 'Selecione o servidor web',
	'ACP_URLREWRITING_SERVER_APACHE'        => 'Apache (.htaccess)',
	'ACP_URLREWRITING_SERVER_NGINX'         => 'NGINX',
	'COPY_CODE'                             => 'Copiar código',

	'ACP_URLREWRITING_APACHE_RULES_ADVANCED'   => '# Vinny URL Rewriting Apache Rules - begin
# IMPORTANTE: coloque estas regras antes das reescritas padrão do phpBB app.php.
# Tópicos
RewriteRule ^.*-t([0-9]+)$ viewtopic.php?t=$1 [QSA,L]
# Posts
RewriteRule ^post-p([0-9]+)$ viewtopic.php?p=$1 [QSA,L]
RewriteRule ^.*-t([0-9]+)-p([0-9]+)$ viewtopic.php?t=$1&p=$2 [QSA,L]
# Fóruns
RewriteRule ^.*-f([0-9]+)$ viewforum.php?f=$1 [QSA,L]

# Sitemap
RewriteRule ^sitemap\.xml$ app.php/sitemap.xml [QSA,L]
# Vinny URL Rewriting Apache Rules - end',
	'ACP_URLREWRITING_APACHE_RULES_SIMPLE'     => '# Vinny URL Rewriting Apache Rules - begin
# IMPORTANTE: coloque estas regras antes das reescritas padrão do phpBB app.php.
# Tópicos
RewriteRule ^topic-t([0-9]+)$ viewtopic.php?t=$1 [QSA,L]
# Posts
RewriteRule ^post-p([0-9]+)$ viewtopic.php?p=$1 [QSA,L]
# Fóruns
RewriteRule ^forum-f([0-9]+)$ viewforum.php?f=$1 [QSA,L]

# Sitemap
RewriteRule ^sitemap\.xml$ app.php/sitemap.xml [QSA,L]
# Vinny URL Rewriting Apache Rules - end',
	'ACP_URLREWRITING_NGINX_RULES_ADVANCED'    => '# Vinny URL Rewriting NGINX Rules - begin
# Tópicos
rewrite ^/(.*)-t([0-9]+)$ /viewtopic.php?t=$2 last;
# Posts
rewrite ^/post-p([0-9]+)$ /viewtopic.php?p=$1 last;
rewrite ^/(.*)-t([0-9]+)-p([0-9]+)$ /viewtopic.php?t=$2&p=$3 last;
# Fóruns
rewrite ^/(.*)-f([0-9]+)$ /viewforum.php?f=$2 last;

# Sitemap
rewrite ^/sitemap\.xml$ /app.php/sitemap.xml last;
# Vinny URL Rewriting NGINX Rules - end',
	'ACP_URLREWRITING_NGINX_RULES_SIMPLE'      => '# Vinny URL Rewriting NGINX Rules - begin
# Tópicos
rewrite ^/topic-t([0-9]+)$ /viewtopic.php?t=$1 last;
# Posts
rewrite ^/post-p([0-9]+)$ /viewtopic.php?p=$1 last;
# Fóruns
rewrite ^/forum-f([0-9]+)$ /viewforum.php?f=$1 last;

# Sitemap
rewrite ^/sitemap\.xml$ /app.php/sitemap.xml last;
# Vinny URL Rewriting NGINX Rules - end',
	'ACP_URLREWRITING_APACHE_FALLBACK_RULES'   => '# Vinny URL Rewriting Apache Fallback Rules - begin
# ----------------------------------------------------------------------
# FALLBACK: REDIRECIONE URLS AMIGÁVEIS PARA O PHPBB PADRÃO
# Use apenas se a extensão "Advanced URL Rewriting" for desinstalada
# ----------------------------------------------------------------------

# 1. Redireciona links de posts (ex: slug-t12-p34 ou post-p34)
RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-t([0-9]+)-p([0-9]+)$
RewriteRule ^.*-t([0-9]+)-p([0-9]+)$ %1/viewtopic.php?t=$1&p=$2 [QSA,R=301,L]

RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-p([0-9]+)$
RewriteRule ^.*-p([0-9]+)$ %1/viewtopic.php?p=$1 [QSA,R=301,L]

# 2. Redireciona links de tópicos (ex: slug-t123 ou topic-t123)
RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-t([0-9]+)$
RewriteRule ^.*-t([0-9]+)$ %1/viewtopic.php?t=$1 [QSA,R=301,L]

# 3. Redireciona links de fóruns (ex: slug-f45 ou forum-f45)
RewriteCond %{REQUEST_URI} ^(.*)/[^/]+-f([0-9]+)$
RewriteRule ^.*-f([0-9]+)$ %1/viewforum.php?f=$1 [QSA,R=301,L]
# Vinny URL Rewriting Apache Fallback Rules - end',
	'ACP_URLREWRITING_NGINX_FALLBACK_RULES'    => '# Vinny URL Rewriting NGINX Fallback Rules - begin
# ----------------------------------------------------------------------
# FALLBACK: REDIRECIONE URLS AMIGÁVEIS PARA O PHPBB PADRÃO
# Use apenas se a extensão "Advanced URL Rewriting" for desinstalada
# ----------------------------------------------------------------------

# 1. Redireciona links de posts (ex: slug-t12-p34 ou post-p34)
rewrite ^(.*)/[^/]+-t([0-9]+)-p([0-9]+)$ $1/viewtopic.php?t=$2&p=$3 permanent;
rewrite ^(.*)/[^/]+-p([0-9]+)$ $1/viewtopic.php?p=$2 permanent;

# 2. Redireciona links de tópicos (ex: slug-t123 ou topic-t123)
rewrite ^(.*)/[^/]+-t([0-9]+)$ $1/viewtopic.php?t=$2 permanent;

# 3. Redireciona links de fóruns (ex: slug-f45 ou forum-f45)
rewrite ^(.*)/[^/]+-f([0-9]+)$ $1/viewforum.php?f=$2 permanent;
# Vinny URL Rewriting NGINX Fallback Rules - end',

	'ACP_URLREWRITING_FAQ_OVERVIEW'         => 'Visão geral',
	'ACP_URLREWRITING_FAQ_OVERVIEW_TEXT'    => 'Esta extensão transforma URLs padrão do phpBB em links amigáveis e adiciona recursos auxiliares para sitemap XML, Open Graph e redirecionamentos 301.',
	'ACP_URLREWRITING_FAQ_FEATURES'         => 'Principais recursos',
	'ACP_URLREWRITING_FAQ_FEATURES_TEXT'    => '<ul><li>URLs amigáveis: substitui links com parâmetros por URLs limpas e legíveis.</li><li>Transliteração: remove acentos e caracteres especiais para gerar slugs ASCII.</li><li>Redirecionamentos 301: encaminha URLs antigas para as novas URLs amigáveis.</li><li>Sitemap XML: gera uma lista de fóruns e tópicos públicos para mecanismos de busca.</li><li>Open Graph: adiciona metadados para melhorar o compartilhamento em redes sociais.</li></ul>',

	'ACP_URLREWRITING_FAQ_FUNCTIONS'        => 'O que cada função faz',
	'ACP_URLREWRITING_FAQ_REWRITE_ENABLE'   => 'Ativa a troca dos links padrão do phpBB por URLs amigáveis. Para funcionar fora do phpBB, o servidor também precisa das regras de reescrita.',
	'ACP_URLREWRITING_FAQ_REWRITE_MODE'     => 'O modo simples usa URLs curtas, como forum-f123 e topic-t456. O modo avançado inclui o título do fórum ou tópico, como nome-do-forum-f123 e titulo-do-topico-t456.',
	'ACP_URLREWRITING_FAQ_TRANSLIT'         => 'Quando ativada, converte caracteres acentuados para equivalentes sem acento, por exemplo ação para acao.',
	'ACP_URLREWRITING_FAQ_REDIRECTS'        => 'Quando ativado, redireciona URLs padrão antigas, como viewtopic.php?t=123, para a URL amigável correspondente.',
	'ACP_URLREWRITING_FAQ_OPENGRAPH'        => 'Adiciona tags Open Graph em tópicos para que links compartilhados exibam título, descrição e imagem quando disponíveis.',

	'ACP_URLREWRITING_FAQ_SITEMAP'          => 'Como o sitemap funciona',
	'ACP_URLREWRITING_FAQ_SITEMAP_TEXT'     => 'O sitemap é gerado em XML e inclui a página inicial, fóruns públicos e tópicos visíveis. Fóruns protegidos por senha, fóruns-link e fóruns excluídos não devem ser publicados.',
	'ACP_URLREWRITING_FAQ_SITEMAP_CACHE'    => 'Define por quantas horas o XML gerado fica em cache. Use 0 apenas se precisar desativar o cache.',
	'ACP_URLREWRITING_FAQ_SITEMAP_LIMIT'    => 'Controla o número máximo de URLs no XML. O limite do protocolo sitemap é 50000 URLs por arquivo.',
	'ACP_URLREWRITING_FAQ_SITEMAP_EXCLUDED' => 'Permite remover fóruns específicos do sitemap, mesmo quando eles são públicos.',

	'ACP_URLREWRITING_FAQ_SERVER'           => 'Como configurar o servidor',
	'ACP_URLREWRITING_FAQ_SERVER_TEXT'      => 'As regras exibidas no módulo Configuração do servidor devem ser copiadas manualmente para o Apache ou NGINX.',
	'ACP_URLREWRITING_FAQ_SERVER_STEPS'     => 'Passo a passo',
	'ACP_URLREWRITING_FAQ_SERVER_STEPS_TEXT'=> '<ol><li>Escolha o modo de URL em Configurações.</li><li>Abra o módulo Configuração do servidor e selecione Apache ou NGINX.</li><li>Copie o bloco correspondente e cole no arquivo de configuração do servidor.</li><li>Limpe o cache do phpBB e teste links de fórum, tópico, post e sitemap.xml.</li></ol>',

	'ACP_URLREWRITING_FAQ_QUESTIONS'        => 'Perguntas frequentes',
	'ACP_URLREWRITING_FAQ_Q_SEO'            => 'Esta é uma extensão de SEO?',
	'ACP_URLREWRITING_FAQ_A_SEO'            => 'Não. O foco é reescrever URLs padrão do phpBB para um formato mais limpo e legível.',
	'ACP_URLREWRITING_FAQ_Q_OLD_LINKS'      => 'Meus links antigos quebram após instalar?',
	'ACP_URLREWRITING_FAQ_A_OLD_LINKS'      => 'Não, desde que os redirecionamentos 301 estejam ativados e as regras do servidor estejam configuradas corretamente.',
	'ACP_URLREWRITING_FAQ_Q_404'            => 'Ativei a extensão e os links retornam 404. O que aconteceu?',
	'ACP_URLREWRITING_FAQ_A_404'            => 'Isso normalmente indica que o servidor ainda não recebeu as regras de reescrita ou que elas foram colocadas na posição errada.',
	'ACP_URLREWRITING_FAQ_Q_NGINX'          => 'Funciona com NGINX?',
	'ACP_URLREWRITING_FAQ_A_NGINX'          => 'Sim. O módulo Configuração do servidor mostra as regras NGINX, mas elas devem ser adicionadas manualmente ao bloco server.',
	'ACP_URLREWRITING_FAQ_Q_CONFLICT'       => 'Pode conflitar com outras extensões de SEO ou URL rewriting?',
	'ACP_URLREWRITING_FAQ_A_CONFLICT'       => 'Sim. Recomenda-se desativar outras extensões de reescrita de URL para evitar conflito nas regras do servidor e na geração de links.',

	'ACP_URLREWRITING_FAQ_UNINSTALL'        => 'Desativação e regras reversas',
	'ACP_URLREWRITING_FAQ_UNINSTALL_WARNING'=> 'Antes de desativar ou remover a extensão, configure regras reversas para evitar erros 404 em URLs amigáveis já indexadas ou compartilhadas.',
	'ACP_URLREWRITING_FAQ_UNINSTALL_TEXT'   => 'As regras abaixo redirecionam URLs amigáveis de volta para os endereços padrão do phpBB usando redirecionamento permanente 301.',
	'ACP_URLREWRITING_FAQ_FALLBACK_APACHE'  => 'Regras reversas para Apache',
	'ACP_URLREWRITING_FAQ_FALLBACK_NGINX'   => 'Regras reversas para NGINX',

	'VIEW_SITEMAP'                          => 'Ver Sitemap',
	'VINNY_URL_SITEMAP_DISABLED'            => 'O sitemap está indisponível porque a extensão URL Rewriting ou o recurso de sitemap está desativado.',
));
