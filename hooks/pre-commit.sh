#!/bin/sh

### Don't allow commiting bad stuff

VAR=$(git diff --cached --diff-filter=ACMR | grep -w "var_dump")
if [ ! -z "$VAR" ]; then
	echo "You've left a var_dump in one of your files! You don't want to commit that..."
	exit 1
fi

exit 0
