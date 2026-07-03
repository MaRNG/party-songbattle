<?php

namespace Deployer;

require 'recipe/composer.php';
require 'contrib/cachetool.php';

set('application', 'party-songbattle');

// PHP-FPM's opcode cache keeps old compiled code in memory across deploys unless reset —
// without this, a deploy can silently keep serving stale application code even though the
// new release is live on disk. Verify this matches the actual php-fpm socket on the server
// (check with `php -v` / the fpm pool config) if opcache resets stop taking effect.
set('cachetool', '/run/php/php8.3-fpm.sock');
set('repository', 'git@github.com:MaRNG/party-songbattle.git');
set('keep_releases', 5);
set('git_tty', false);

add('shared_files', ['config/local.neon']);
add('shared_dirs', ['var/log', 'var/temp']);
add('writable_dirs', ['var/log', 'var/temp']);
set('writable_mode', 'chown');
set('http_user', 'www-partysongbattlemarngdev');

host('production')
    ->setHostname('194.163.187.217')
    ->set('remote_user', 'root')
    ->set('deploy_path', '/www/party-songbattle.marng.dev');

desc('Build frontend assets');
task('deploy:frontend', function () {
    cd('{{release_path}}');
    run('npm ci');
    run('npm run build');
});

desc('Own the release by the project system user');
task('deploy:chown_release', function () {
    run('chown -R {{http_user}}:{{http_user}} {{release_path}}');
});

desc('Run database migrations');
task('deploy:migrate', function () {
    cd('{{release_path}}');
    // Run as the project's own system user, not root, so Nette's DI
    // container cache in shared var/temp isn't left root-owned and
    // unwritable/undeletable by PHP-FPM on the next request.
    run('sudo -u {{http_user}} {{bin/php}} bin/console migrations:migrate --no-interaction');
});

after('deploy:vendors', 'deploy:frontend');
after('deploy:frontend', 'deploy:chown_release');
after('deploy:chown_release', 'deploy:migrate');

// Reset opcache once the new release is live (after the symlink flip), not before —
// resetting earlier would just get warmed back up with the old release's code.
after('deploy:symlink', 'cachetool:clear:opcache');

after('deploy:failed', 'deploy:unlock');
