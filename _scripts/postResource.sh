#!/bin/bash
#
# Copyright 2014 Jérôme Gasperi
#
# Licensed under the Apache License, version 2.0 (the "License");
# You may not use this file except in compliance with the License.
# You may obtain a copy of the License at:
#
#   http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
# WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
# License for the specific language governing permissions and limitations
# under the License.

HTTPS=0
HOST=localhost
TARGET=resto
usage="## Post a resource to collection\n\n  Usage $0 -c <Collection name> -f <Resource description file> -u <username:password> [-s (use https if set)  -H server (default localhost) -p resto path (default resto)]\n"
while getopts "sf:c:u:p:hH:" options; do
    case $options in
        u ) AUTH=`echo $OPTARG`;;
        H ) HOST=`echo $OPTARG`;;
        p ) TARGET=`echo $OPTARG`;;
        f ) FILE=`echo $OPTARG`;;
        s ) HTTPS=1;;
        c ) COLLECTION=`echo $OPTARG`;;
        h ) echo -e $usage;;
        \? ) echo -e $usage
            exit 1;;
        * ) echo -e $usage
            exit 1;;
    esac
done
if [ "$FILE" = "" ]
then
    echo -e $usage
    exit 1
fi
if [ "$COLLECTION" = "" ]
then
    echo -e $usage
    exit 1
fi

if [ "$HTTPS" = "1" ]
then
    curl -k -X POST -d @$FILE https://$AUTH@$HOST/$TARGET/collections/$COLLECTION
else
    curl -X POST -d @$FILE http://$AUTH@$HOST/$TARGET/collections/$COLLECTION
fi
echo ""
