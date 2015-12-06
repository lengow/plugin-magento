#!/bin/bash
MAGE=$1

copy_file(){
    ORIGINAL_FILE="$PWD$1"
    DESTINATION_FILE="$MAGE$1"
    if [ -f "$ORIGINAL_FILE" ]; then
        if [ -f "$DESTINATION_FILE" ]; then
            rm $DESTINATION_FILE
        fi
        ln -s $ORIGINAL_FILE $DESTINATION_FILE
        echo "✔ Create file : $DESTINATION_FILE"
    else
        echo "⚠ Missing file : $ORIGINAL_FILE"
    fi
    return $TRUE
}

copy_directory(){
    ORIGINAL_DIRECTORY="$PWD$1"
    DESTINATION_DIRECTORY="$MAGE$1"
    if [ -d "$ORIGINAL_DIRECTORY" ]; then
        if [ -e "$DESTINATION_DIRECTORY" ]; then
            unlink $DESTINATION_DIRECTORY
        fi
        ln -s $ORIGINAL_DIRECTORY $DESTINATION_DIRECTORY
        #echo "ln -s $ORIGINAL_DIRECTORY $DESTINATION_DIRECTORY"
        echo "✔ Create directory : $DESTINATION_DIRECTORY"
    else
        echo "⚠ Missing directory : $ORIGINAL_DIRECTORY"
    fi
    return $TRUE
}

create_if_not_exist(){
    if ! [ -d "$1" ]; then
      mkdir $1
    fi
}

copy_file "/app/etc/modules/Lengow_Connector.xml"
copy_file "/app/design/frontend/base/default/layout/lengow.xml"
copy_file "/app/design/adminhtml/default/default/layout/lengow.xml"
copy_directory "/app/code/community/Lengow"
copy_directory "/app/design/frontend/base/default/template/lengow"
copy_directory "/app/design/adminhtml/default/default/template/lengow"
copy_directory "/skin/adminhtml/default/default/lengow"
copy_directory "/skin/frontend/base/default/lengow"
copy_directory "/media/lengow"
create_if_not_exist "$MAGE/app/locale/fr_FR"
copy_file "/app/locale/fr_FR/Lengow_Connector.csv"

exit 0;
