#!/bin/bash
# Automation manager - Service to manage the selenium server instances
# 
# chkconfig: 35 20 80
# descripton: Service to manager selenium service instances
# Declare global array variables
declare -a LISTVERSIONS
declare -a INSTANCES
declare -a CONFIG_DATA
declare -a SELENIUM_PIDLIST
declare -a XVFB_PIDLIST
# End
# Set variables for the default config
CONFIG_PORT=4444
CONFIG_DISPLAY=99
CONFIG_TIMEOUT=0
CONFIG_BROWSERTIMEOUT=0
CONFIG_SELENIUM=/opt/selenium/selenium-server.jar
CONFIG_DATA=($CONFIG_PORT $CONFIG_DISPLAY $CONFIG_TIMEOUT $CONFIG_BROWSERTIMEOUT $CONFIG_SELENIUM)
CONFIG_FILE=/etc/default/automation-manager
# End
# Set variables for program locations to call
SED=$(which sed 2> /dev/null)
JAVA=$(which java 2> /dev/null)
FIREFOX=$(which firefox 2> /dev/null)
CHROMEDRIVER=$(which chromedriver 2> /dev/null)
XVFB=$(which xvfb 2> /dev/null)
GREP=$(which grep 2> /dev/null)
AWK=$(which awk 2> /dev/null)
HEAD=$(which head 2> /dev/null)
TAIL=$(which tail 2> /dev/null)
TOUCH=$(which touch)
CAT=$(which cat)
WC=$(which wc)
NETSTAT=$(which netstat)
RM=$(which rm)
# End
# Set other variables
COUNT=0
INSTANCE_COUNT=0
XVFBSCREENRES=1024x768x8
TMPCACHEFILE=/tmp/amcache.tmp
PIDFILE=/run/amselenium.pid
# End

if [ -z $XVFB ]; then
    XVFB=$(which Xvfb)
fi

check_config_file() {

	if [ ! -e $CONFIG_FILE ]; then

		# Create an empty config file if it does not exist
		$TOUCH $CONFIG_FILE
		
		if [ -e $CONFIG_FILE ]; then

			# Append default config values to the config file
			for CONFIG_VALUE in CONFIG_DATA
			do
				# Append config value to the config file
				echo $CONFIG_VALUE >> $CONFIG_FILE
			done
		else
			echo -e "The config file $CONFIG_FILE does not exist and failed to create it"
		fi
	fi
}

# Check if config file exists else create it with default config
check_config_file

# Load config
source $CONFIG_FILE

load_pids_from_pid_file() {

	# Remove when ready for production
	# Temp config file for dev and testing
	# Replace all TMPPIDFILE references to PIDFILE
	local TMPPIDFILE=./testconfigfile

	if [ -e $TMPPIDFILE ]; then

		for PIDITEM in $($CAT $TMPPIDFILE | $SED -n -e '/^#/!p')
		do
			# Run grep on line to check if the line is commented
			IS_COMMENT=$(echo $PIDITEM | $GREP -P '^#')

			if [ -z $IS_COMMENT ]; then

				# Get instance #
				PIDITEM_INSTANCENO=$(echo $PIDITEM | $AWK -F',' '{print $1}')

				# Get Selenium PID
				PIDITEM_SELENIUMPID=$(echo $PIDITEM | $AWK -F',' '{print $2}')
				
				# Get Xvfb PID
				PIDITEM_XVFBPID=$(echo $PIDITEM | $AWK -F',' '{print $3}')

				# Get Port #
				PIDITEM_PORT=$(echo $PIDITEM | $AWK -F',' '{print $4}')

				# Get display #
				PIDITEM_DISPLAY=$(echo $PIDITEM | $AWK -F',' '{print $5}')

				# Get PHPUnit PID ID
				PIDITEM_PHPUNITPID=$(echo $PIDITEM | $AWK -F',' '{print $6}')

				if [ -n "$PIDITEM_SELENIUMPID" -a -n "$PIDITEM_INSTANCENO" ]; then

					SELENIUM_PIDLIST[$PIDITEM_INSTANCENO]=$PIDITEM_SELENIUMPID
				fi

				if [ -n "$PIDITEM_XVFBPID" -a -n "$PIDITEM_INSTANCENO" ]; then

					XVFB_PIDLIST[$PIDITEM_INSTANCENO]=$PIDITEM_XVFBPID
				fi
			fi

		done
		# Return 1 as it is believed to have completed successfully at this point
		echo 1
	else
		# Return 0 to indicate fail to load PID IDs from the file
		echo 0
	fi

}

function remove_pid() {

	# Remove when ready for production
	# Temp config file for dev and testing
	# Replace all TMPPIDFILE references to PIDFILE
	local TMPPIDFILE=./testconfigfile

    if [ -e $TMPPIDFILE -a -n $1 ]; then

        # Prepare param for sed
        SEDPARAMS="/$1/d"
        
        # Remove PID from PIDFILE
        $SED -i $SEDPARAMS $TMPPIDFILE

        # Prepare param for sed
        SEDPARAMS="/$1/p"

        # Check if has been removed from PID file
        RESULT=$($SED -n -e $SEDPARAMS $TMPPIDFILE)

		if [ -z "$RESULT" ]; then
			echo 1
		else
			echo 0
		fi

    else
		echo 0
    fi
}

function kill_program() {

	if [ -n "$1" ]; then

		RESULTPID=$(ps $1 | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)

		if [ -z "$RESULTPID" ]; then
			echo 1
		else
			# Issue SIGTERM signal to process
			kill -15 $1
			# Wait 2 seconds to allow it to exit gracefully
			sleep 2
			# Check if process has exited
			RESULTPID=$(ps $1 | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)
			
			if [ -n "$RESULTPID" ]; then
				# If we reach here its generally because the program is ignoring
				# the SIGTERM we issued to it and/or its frozen

				# Issue SIGKILL signal to process
				kill -9 $1

				# Check if process has exited
				RESULTPID=$(ps $1 | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)

				if [ -n "$RESULTPID" ]; then
					echo 0
				else
					echo 1
				fi
			else
				echo 1
			fi

			echo 1
		fi

	else
		echo 0
	fi

}

verify_pids_in_pid_file() {
	#run_instance
	load_pids_from_pid_file

	RESULT=$(load_pids_from_pid_file)
	local XCOUNT=0

	if [ $RESULT -eq 1 ]; then

		for ITEM_SELENIUM in "${SELENIUM_PIDLIST[@]}"
		do
			XCOUNT=$((XCOUNT + 1))

			# Check if selenium process is running
			RESULT_SELENIUM_PID=$(ps ${SELENIUM_PIDLIST[$XCOUNT]} | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)
			RESULT_XVBF_PID=$(ps ${XVFB_PIDLIST[$XCOUNT]} | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)
			
			if [ -z "$RESULT_SELENIUM_PID" -o -z "$RESULT_XVBF_PID" ]; then

				if [ -n "$RESULT_SELENIUM_PID" ]; then

					kill_program $RESULT_SELENIUM_PID

				elif [ -n "$RESULT_XVBF_PID" ]; then

					kill_program $RESULT_XVBF_PID
					
				fi

				PARAMS=".*${SELENIUM_PIDLIST[$XCOUNT]},${XVFB_PIDLIST[$XCOUNT]}.*"
				RESULT=$(remove_pid $PARAMS)
				echo -e "Result of attempt to remove line with dead PID entries: $RESULT"

			fi 

		done
	else
		echo 0
	fi
}

function delete_pid_file() {

	if [ -e $PIDFILE ]; then

		# Delete pid file
		$RM $PIDFILE

		if [ ! -e $PIDFILE ]; then
			echo 1
		else
			echo 0
		fi

	else
		echo 1
	fi

}

function create_pid_file() {

	if [ ! -e $PIDFILE ]; then
		$TOUCH $PIDFILE
	fi
}

function check_pid() {
	# add some code here
	local SOME_VAR="test"
}

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

function get_pid_for_program() {
    
    # The function requires 1 argument to be passed to it.
    # The argument required is a regexp value to be used
    # to find the process using grep
    
    if [ -n "$1" ]; then
    
        # Run ps to and pipe out to grep to fetch a matching process
        PSQUERY=$(ps au | $GREP -Pi "$1" | $SED -n -e '/grep/!p')
        
        if [ -n "$PSQUERY" ]; then
            
            # Manipulate text string to extract the PID
            RESULTPID=$(echo $PSQUERY | $AWK '{print $2}')
            
            if [ -n "$RESULTPID" ]; then
                echo $RESULTPID
            else
                echo 0
            fi
            
        else
            echo 0
        fi
        
    else
        echo 0
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

function find_free_port() {

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

function find_free_display() {

    local FOUNDDISPLAY=0
	local TEMPDISPLAY=$DISPLAY
    
    while [ $FOUNDDISPLAY -eq 0 ]
    do
		result=$(ps au | $GREP -i 'xvfb' | $GREP $TEMPDISPLAY )
        
        if [ -z "$result" ]; then
            FOUNDDISPLAY=1
        else
            if [ $TEMPDISPLAY -ge 1 ]; then
                TEMPDISPLAY=$((TEMPDISPLAY - 1))
            else
                echo -e "All displays seem to be used up"
                exit 1
            fi
        fi
    done
    
    echo $TEMPDISPLAY
}

function run_instance() {

	local FREE_PORT=$(find_free_port)
	local FREE_DISPLAY=$(find_free_display)
	local NEW_XVFB_LOGFILE=/var/log/xvfb_$FREE_PORT.log
	local NEW_SELENIUM_LOGFILE=/var/log/selenium_$FREE_PORT.log

	$XVFB :$FREE_DISPLAY -ac -screen 0 $XVFBSCREENRES > $NEW_XVFB_LOGFILE 2>&1 &
	export DISPLAY=localhost:$FREE_DISPLAY.0
	$JAVA -jar $SELENIUM -port $FREE_PORT > $NEW_SELENIUM_LOGFILE 2>&1 &

	GREP_PARAMS="$XVFB.*$FREE_DISPLAY"
	local NEW_XVFB_PID=$(get_pid_for_program $GREP_PARAMS)
	GREP_PARAMS="$SELENIUM.*$FREE_PORT"
	local NEW_SELENIUM_PID=$(get_pid_for_program $GREP_PARAMS)
	
	if [ $NEW_XVFB_PID -gt 1 -a $NEW_SELENIUM_PID -gt 1 ]; then

		verify_pids_in_pid_file

		# kill newly started processes for dev purposes
		echo -e "Print ps output to show new processes"
		ps au | grep -Pi 'selenium|xvfb'
		#echo -e "Kill newly created processes"
		#kill -15 $NEW_XVFB_PID $NEW_SELENIUM_PID
		#sleep 1
		#ps au | grep -Pi 'selenium|xvfb'
		# End
	fi
	
}

# This function is intended to find unmanaged instances of
# selenium and and add it to the PID file to be managed
function find_unmanaged_instances() {
	local VAR
	# Add some code here
}

case "$1" in
start)
	verify_pids_in_pid_file
    exit 0
    
    if [ -z $PID ]
    then
        echo "Starting a selenium instance..."
        `export DISPLAY=$DISPLAY`
        `java -jar $SELENIUM -port 4444 -browserName=$BROWSER > /var/log/selenium.log 2>&1 &`

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

    if [ -z $PID ]
    then
        echo "Selenium service is not running."
        echo "Usage: sudo service selenium { start|stop|status }"
    else
        echo -e "Requesting process to gracefully terminate by sending signal: SIGTERM"
        `kill -15 $PID`

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
