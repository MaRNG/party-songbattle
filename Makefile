clean-temp:
	- rm -rf var/temp/*

clean-log:
	- rm -rf var/log/*

clean: clean-temp clean-log

deploy:
	php vendor/bin/dep deploy production