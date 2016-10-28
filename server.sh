#!/bin/bash

processMark=": reserve process";

function start(){
    echo 'starting swooler server...'

    php test/testProcess.php  >> log/server.log 2>&1


    printf $?
    if [ $? == 0 ]; then
        printf "\server start OK\r\n"
        return 0
    else
        printf "\server start FAIL\r\n"
        return 1
    fi
}

function stop(){

    $(ps aux  | grep "$processMark" |grep -v "grep "| awk '{print $2}'    | xargs  kill -9) 

    PROCESS_NUM2=$(ps aux  | grep "$processMark" |grep -v "grep "| awk '{print $2}'   | wc -l )    
    if [ $PROCESS_NUM2 == 0 ]; then
        printf "\server stop OK\r\n"
        return 0
    else
        printf "\server stop FAIL\r\n"
        return 1
    fi

}


case $1 in 
    
    start )
        start
    ;;
    stop)
        stop
    ;;
    restart)
        stop
        sleep 1
        start
    ;;

    *)
        start
    ;;
esac