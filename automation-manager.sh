#!/bin/bash
# Automation manager - Service to manage the selenium server instances
# 
# chkconfig: 35 20 80
# descripton: Service to manager selenium service instances
declare -a LISTVERSIONS
declare -a INSTANCES
CONFIG_FILE=/etc/default/selenium-server
SELENIUM="/opt/selenium/selenium.jar"
DISPLAY="localhost:99.0"
SED=$(which sed 2> /dev/null)
JAVA=$(which java 2> /dev/null)
FIREFOX=$(which firefox 2> /dev/null)
CHROMEDRIVER=$(which chromedriver 2> /dev/null)
XVFB=$(which xvfb 2> /dev/null)
GREP=$(which grep 2> /dev/null)
AWK=$(which awk 2> /dev/null)
TOUCH=$(which touch)
CAT=$(which cat)
WC=$(which wc)
NETSTAT=$(which netstat)
COUNT=0
INSTANCE_COUNT=0
TMPCACHEFILE=/tmp/amcache.tmp
PIDFILE=/run/amselenium.pid

PID=''

if [ -z $XVFB ]; then
    XVFB=$(which Xvfb)
fi

# Fetch config
source $CONFIG_FILE

function add_pid() {
    if [ -e $PIDFILE ]; then
        $CAT $PID >> $PIDFILE
    else
        $TOUCH $PIDFILE
        if [ -e $PIDFILE ]; then
            echo -e "PID file created: $PIDFILE"
        else
            echo -e "Failed to create PID file"
            exit 1
        fi
    fi
}

function remove_pid() {
    if [ -e $PIDFILE ]; then
        # Prepare param for sed
        SEDPARAMS="/$PID/d"
        
        # Remove PID from PIDFILE
        $SED -i $SEDPARAMS $PIDFILE
        
    else
        # Create PID file
        $TOUCH $PIDFILE
        
        # Recheck if PID file exists and fail if it does not
        if [ -e $PIDFILE ]; then
            echo -e "PID file created: $PIDFILE"
        else
            echo -e "Failed to create PID file"
            exit 1
        fi
    fi
}

function check_requirements() {

    # Check for sed and that it is executable
    if [ ! -x $SED ]; then
        echo -e "Sed was not found and/or is not executable but is required"
        exit 1
    fi
    
    # Check for JAVA and that it is executable
    if [ ! -x $JAVA ]; then
        echo -e "JAVA was not found and/or is not executable but is required"
        exit 1
    fi
    
    # Check for Firefox and that it is executable
    if [ ! -x $FIREFOX ]; then
        echo -e "Firefox was not found and/or is not executable but is required"
        exit 1
    fi
    
    # Check for Chromedriver and that it is executable
    if [ ! -x $CHROMEDRIVER ]; then
        echo -e "Chromedriver was not found and/or is not executable but is required"
        exit 1
    fi
    
    # Check for Xvfb and that it is executable
    if [ ! -x $XVFB ]; then
        echo -e "Xvfb was not found and/or is not executable but is required"
        exit 1
    fi
    
    # Check for awk and that it is executable
    if [ ! -x $AWK ]; then
        echo -e "Awk was not found and/or is not executable but is required"
        exit 1
    fi
    
    # Check for grep and that it is executable
    if [ ! -x $GREP ]; then
        echo -e "Grep was not found and/or is not executable but is required"
        exit 1
    fi
    
    # Check for netstat and that it is executable
    if [ ! -x $NETSTAT ]; then
        echo -e "netstat was not found and/or is not executable but is required"
        exit 1
    fi
}

function getpid() {
    PID=`ps aux | $SELENIUM`
    PID=${PID:9:5}
}

function listselenium() {
    # Print some information about what the program is doing
    echo -e "Searching for selenium jar files. Please wait..."
    
    # Run find command to find all selenium jar files on the system
    FOUND=$(find / -type f -iname 'selenium*.jar' 2> /dev/null)
    echo -e "Listing..."
    
    if [[ -n "$FOUND" ]]; then
        COUNT=0
        for seleniumfile in $FOUND
        do
            COUNT=$((COUNT + 1))
            echo -e "$COUNT. $seleniumfile"
            LISTVERSIONS[$COUNT]=$seleniumfile
        done
    else
        echo -e "Could not find any selenium jar files on the system"
    fi
}

function set_default_selenium() {
    
    # Save what the current default selenium is to compare later
    OLDSELENIUM=$SELENIUM
    
    if [ "$#" -ge 2 ]; then
        REPLY=$2
    else
        # List selenium jar files found on system for user
        listselenium
        echo -e ""
        echo -e "Please select a new default selenium server:"
    
        # Prompt user to make selection
        read REPLY
    fi
    
    # If input is valid then save new selenium instance
    if [ $REPLY -le $COUNT -a $REPLY -ge 1 ]; then
    
        SELENIUM="${LISTVERSIONS[$REPLY]}"
        
        if [ -n "$SELENIUM" -a "$SELENIUM" != "$OLDSELENIUM" ]; then
            echo -e "New selenium server default is: $SELENIUM"
        else
            echo -e "Selenium default not changed because selected option is already the default"
        fi
    else
        echo -e "Invalid option: $REPLY"
    fi
    
    if [ -f $CONFIG_FILE ]; then

        NEW_DEFAULT=$(echo $SELENIUM | $SED 's/\//\\\//g')
        SEDPARAMS="s/SELENIUM=.*/SELENIUM=$NEW_DEFAULT/"
        $SED -i $SEDPARAMS $CONFIG_FILE
        VERIFY=`$GREP "$SELENIUM" $CONFIG_FILE`
        
        if [ -z "$VERIFY" ]; then
            echo -e "Could not verify that default was set in file: $CONFIG_FILE"
            exit 1
        fi
        
    else
        echo -e "Config file is not writeable or does not exist: $CONFIG_FILE"
    fi
}

function update_running_instances() {

    for INSTANCE in $($CAT $PIDFILE)
    do
        #INSTANCES[]
        echo -e "test"
    done
}

function get_instance_count() {

    if [ -e $PIDFILE ]; then
        INSTANCE_COUNT=$($WC -l $PIDFILE | $AWK '{{print $1}}')
    else
        echo -e "PID file does not exist and therefore there is no instances handled by this service"
    fi
    
}

function check_if_port_is_available() {

    if [ -n "$1" ]; then
        result=$1
        # Set sed params
        SEDPARAMS="/^tcp.*$1.*/p"
        # Run netstat command to determine if port is been used
        CHECKPORT=$($NETSTAT -tl | $SED -n -e $SEDPARAMS)
        
        # Return 1 if port is free else 0
        if [ -z "$CHECKPORT" ]; then
            echo 1
        else
            echo 0
        fi
        
    else
        echo -e "Please supply the port number as an argument to the function when calling it"
        exit 1
    fi
}

find_free_port() {

    local FOUNDPORT=0
    
    while [ $FOUNDPORT -eq 0 ]
    do
        result=$(check_if_port_is_available $PORT)
        
        if [ $result -eq 1 ]; then
            FOUNDPORT=1
        else
            PORT=$((PORT + 1))
        fi
    done
    
    echo $PORT
}

case "$1" in
start)
    get_instance_count

    FREEPORT=$(find_free_port)
    echo -e "Available port: $FREEPORT"
    exit 0
    getpid
    if [ -z $PID ]
    then
        echo "Starting a selenium instance..."
        `export DISPLAY=$DISPLAY`
        `java -jar $SELENIUM -port 4444 -browserName=$BROWSER > /var/log/selenium.log 2>&1 &`
        getpid
        if [ -z $PID ]
        then
            echo "Selenium service failed to start. Please logs below:"
            tail /var/log/selenium.log
            echo "Print out netstat listing for port 4444:"
            `netstat -ntlp | grep -i 4444`
        else
            echo "Selenium service started successfully. PID: $PID"
        fi
    else
        "Selenium service has already been started: PID: $PID"
    fi
    echo
    ;;
stop)
    getpid
    if [ -z $PID ]
    then
        echo "Selenium service is not running."
        echo "Usage: sudo service selenium { start|stop|status }"
    else
        echo -e "Requesting process to gracefully terminate by sending signal: SIGTERM"
        `kill -15 $PID`
        getpid
        if [ -z $PID ]
        then
            echo -e "Selenium service has successfully stopped."
        else
            echo -e "Selenium service was unable to stop for an unknown reason."
            echo -e "Do you want to forcefully end the process?"
            read
            
            if [[ -n "$REPLY" && "$REPLY" == [Yy]* ]]; then
            
                echo -e "Attempting to forcefully kill process by sending signal: SIGKILL"
                `kill -9 $PID`
                
            elif [[ -n "$REPLY" && "$REPLY" == [Nn]* ]]; then
                echo -e "Process not terminated"
            else
                echo -e "You did not provide input and the program has defaulted to NO"
            fi
        fi
    fi
    echo
    ;;
status)
    getpid
    if [ -z $PID ]
    then
        echo "Selenium service is not running."
    else
        echo "Selenium service is running. PID: $PID"
    fi
    ;;
list-selenium)
    listselenium
    echo -e "Default selenium server: $SELENIUM"
    ;;
set-selenium)
    set_default_selenium
    ;;
*)
    echo "Usage: sudo service selenium { start|stop|status|list-selenium|set-selenium }"
    exit 1
esac
exit 0