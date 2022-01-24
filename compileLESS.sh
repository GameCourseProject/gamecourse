#!/bin/bash

# compile less styles
echo '- Compiling LESS files to generate CSS'
for file in $(find . -path .git -prune -o -name '*.less'); do
    outfile=`dirname $file`/"../"`basename $file .less`.css
    lessc --no-color --strict-imports $file $outfile
    find $outfile -empty -exec rm {} \;
done

echo 'Done'