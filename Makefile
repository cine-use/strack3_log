phinx_bin=vendor/robmorgan/phinx/bin/phinx
sd_bin=bin/start_swoole_server.php
db_name=strackjob

help:
	@echo 'Here is the command list:'
	@echo '------------------------'
	@cat Makefile |grep '^\w.*:$$'

vendor_refresh:
	@rm -rf vendor
	@composer config -g repo.packagist composer https://packagist.laravel-china.org
	@composer update
## swoole distributed
sd_test:
	@php $(sd_bin) test

sd_up:
	@php $(sd_bin) start

sd_up_background:
	@php $(sd_bin) start -d

## database setting
### rabbitmq set
mq_init:
	@/usr/sbin/rabbitmqctl add_user strack strack;
	@/usr/sbin/rabbitmqctl set_user_tags strack administrator;
	@/usr/sbin/rabbitmqctl set_permissions -p / strack '.*' '.*' '.*';

### mysql set and db table set
db_create:
	@mysql -uroot -pStrack2016! -e "create database if not exists $(db_name) default charset utf8 collate utf8_general_ci"

db_rollback:
	@php $(phinx_bin) rollback -t 0

db_migrate:
	@php $(phinx_bin) migrate

db_seed:
	@php $(phinx_bin) seed::run

db_install:
	make db_create
	make mq_init

db_update:
	make db_migrate

awesome_init:
	make mq_init
	make db_install
	make vendor_refresh
	make db_migrate
