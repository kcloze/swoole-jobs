#!/bin/bash

#启动脚本
processFile="test/testProcess.php"


#不同的系统，进程标识不同,主要是mac机器不支持进程重命名
if [ "$(uname)" == "Darwin" ]; then
    # Do something under Mac OS X platform
    processMark=$processFile;
        
elif [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
    # Do something under GNU/Linux platform
    processMark=": reserve process";
    
elif [ "$(expr substr $(uname -s) 1 10)" == "MINGW32_NT" ]; then
    # Do something under Windows NT platform
    printf "not support in windows \r\n"
    exit
fi



function start(){
    echo 'starting swooler server...'

    php $processFile  >> log/server.log 2>&1


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