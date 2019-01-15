#!/bin/bash
cd /var/www/html
file="DEV"
if [ ! -f $file ] ; then
    echo -e "This script can only be run on DEV!"
	exit 1
fi
if (( "$#" != 1 )) 
then
	echo -e "Please provide a commit message eg. 'fixed a load of stuff'"
	exit 1
fi
file=".git/index.lock"
if [ -f $file ] ; then
    rm -f $file
fi
cmd_output=$(/usr/bin/git add . 2>&1)
msg="$*"
cmd_output=$(/usr/bin/git commit -m "$msg" 2>&1)

echo $cmd_output
cmd_output=$(/usr/bin/git push origin master 2>&1)
echo $cmd_output
cmd_output=$(/usr/bin/ssh -i /home/ubuntu/Acas-dev.pem ubuntu@34.243.107.7 'cd /var/www/html; git pull origin master' 2>&1)
if [[ $cmd_output = *"index.lock': File exists"* ]]; then
	cmd_output=$(/usr/bin/ssh -i /home/ubuntu/Acas-dev.pem ubuntu@34.243.107.7 'rm -f /var/www/html/.git/index.lock' 2>&1)
	cmd_output=$(/usr/bin/ssh -i /home/ubuntu/Acas-dev.pem ubuntu@34.243.107.7 'cd /var/www/html; git pull origin master' 2>&1)
fi
if [[ $cmd_output = *"Your local changes to the following files would be overwritten by merge"* ]]; then
	echo -e "\n\e[0;31m*** WARNING Git Pull on UAT failed ***\e[00m\n"
	echo $cmd_output
	exit 1
fi
echo $cmd_output
echo -e "\nRunning 'composer update', 'drush updb' and 'drush cr' on UAT. Please wait...\n"
cmd_output=$(/usr/bin/ssh -i /home/ubuntu/Acas-dev.pem ubuntu@34.243.107.7 'cd /var/www/html; composer update; drush updb; drush cr' 2>&1)
echo $cmd_output
echo -e "\n\e[1;32mFinished release to UAT :)\e[00m\n"
