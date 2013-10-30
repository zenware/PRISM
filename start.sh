#!/bin/bash
php -e bootstrap.php %1 %2
if [ $? != 0 ]; then
echo "PRISM Crashed, Restarting in 15 Seconds.."; sleep 15
sh $0
fi
