#!/bin/sh

#When packaging up a module to move environments we need to make sure the requrie* and include* dependencies are met!

echo "ensure dependencies are met! But don't include files that aren't tested"
grep require *php
grep include *php

cd ..
tar czvf ksf_payment_destinations.tgz ksf_payment_destinations ksf_modules_common/*php
