#!/bin/bash
#JISON_PHP=$(pwd)/"../jison_test/jison/ports/php/php.js"
JISON_PHP="/c/jison-master/ports/php/php.js"
echo 'SmartBoards essencial files generation'

# generate view expression parser
echo '- Generating Parsers'
pwd=$(pwd)
cd modules/views/Expression/
node $JISON_PHP SmartboardsExpression.jison
cd $pwd
echo '-- Done'

echo 'Done'