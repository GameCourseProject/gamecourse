#!/bin/bash
#JISON_PHP=$(pwd)/"../jison_test/jison/ports/php/php.js"
JISON_PHP="/c/jison-master/ports/php/php.js"
echo 'SmartBoards essencial files generation'

# compile less styles
echo '- Compiling LESS'
for file in $(find . -path .git -prune -o -name '*.less'); do
    outfile=`dirname $file`/"../"`basename $file .less`.css
    lessc --no-color --strict-imports $file $outfile
    find $outfile -empty -exec rm {} \;
#done
echo '-- Done'

# generate view expression parser
echo '- Generating Parsers'
pwd=$(pwd)
cd modules/views/Expression/
node $JISON_PHP SmartboardsExpression.jison
cd $pwd
echo '-- Done'

echo 'Done'
