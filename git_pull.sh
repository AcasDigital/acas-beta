#!/bin/bash
cd /var/www/html
file=".git/index.lock"
if [ -f $file ] ; then
  rm -f $file
fi
FILE="/var/log/git_pull.log"
cmd_output=$(sudo -u ubuntu /usr/bin/git pull origin master 2>&1)
echo $cmd_output >> $FILE
if [[ $cmd_output = *"Already up-to-date"* ]]; then
	echo "0"
	exit
fi
cmd_output=$(sudo -u ubuntu /usr/local/bin/composer update 2>&1)
echo $cmd_output >> $FILE
echo "1"