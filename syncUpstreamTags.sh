#!/bin/bash

#git remote add upstream https://github.com/openthinklabs/qti-sdk.git
git fetch --tags upstream
git push -f --tags origin master

