#!/bin/bash
# automation-manager.sh
#
# @package Automation Manager
# @author Clinton Wright <clintonabco@gmail.com>
# @copyright 2016 onwards Clinton Shane Wright
# @license GNU GPL
# @link http://www.gnu.org/licenses/gpl.html
#      # This program is free software: you can redistribute it and/or modify
#       it under the terms of the GNU General Public License as published by
#       the Free Software Foundation, either version 3 of the License, or
#       (at your option) any later version.
#      
#       This program is distributed in the hope that it will be useful,
#       but WITHOUT ANY WARRANTY; without even the implied warranty of
#       MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#       GNU General Public License for more details.
#      
#       You should have received a copy of the GNU General Public License
#       along with this program. If not, see <http://www.gnu.org/licenses/>.

# Declare global array variables
declare -a LISTVERSIONS
declare -a INSTANCES
declare -a CONFIG_DATA
declare -a SELENIUM_PIDLIST
declare -a XVFB_PIDLIST

# End
# Set variables for the default config
CONFIG_PORT="PORT=4444"
CONFIG_DISPLAY="DISPLAY=99"
CONFIG_TIMEOUT="TIMEOUT=0"
CONFIG_BROWSERTIMEOUT="BROWSERTIMEOUT=0"
CONFIG_SELENIUM="SELENIUM=/opt/selenium/selenium-server.jar"
CONFIG_FANDV_SCRIPT="SCRIPT_FANDV=$HOME/BWT/MAXSelenium/MAXLive_CreateFandVContracts.php"
CONFIG_NCP_RATES_SCRIPT="SCRIPT_NCP_RATES=$HOME/BWT/MAXSelenium/MAXLive_NCP_Rates_Update.php"
CONFIG_DATA=($CONFIG_PORT $CONFIG_DISPLAY $CONFIG_TIMEOUT $CONFIG_BROWSERTIMEOUT $CONFIG_SELENIUM $CONFIG_FANDV_SCRIPT $CONFIG_NCP_RATES_SCRIPT)
CONFIG_FILE="$HOME/.automation-manager.conf"
PIDFILE="$HOME/.automation-manager.pid"
TMPCACHEFILE=/tmp/amcache.tmp
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
PS=$(which ps)
PHPUNIT=$(which phpunit)
# End
# Set other variables
COUNT=0
INSTANCE_COUNT=0
XVFBSCREENRES=1024x768x8
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
			for CONFIG_VALUE in ${CONFIG_DATA[@]}
			do
				# Append config value to the config file
				echo $CONFIG_VALUE >> $CONFIG_FILE
			done
		else
			echo -e "The config file $CONFIG_FILE does not exist and failed to create it"
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

    # Check for netstat and that it is executable
    if [ ! -x $PHPUNIT ]; then
        echo -e "PHPUnit was not found and/or is not executable but is required"
        exit 1
    fi
    
    # Check for selenium and that it is executable
    if [ ! -x $SELENIUM ]; then
        clear && clear && clear
        echo -e "ERROR:"
        echo -e "selenium-server.jar was not found and/or is not executable but is required"
        echo -e "Please complete the following steps:"
        echo -e "1. Run the following command to list the available selenium jar files:"
        echo -e "automation-manager.sh list-selenium"
        echo -e ""
        echo -e "2. Run the following command but to set the default selenium to use:"
        echo -e "automation-manager.sh set-selenium"
        exit 1
    fi
}

# Check if config file exists else create it with default config
check_config_file

# Load config
source $CONFIG_FILE

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

function kill_program() {

	if [ -n "$1" ]; then

		RESULTPID=$($PS -o pid,user,command $1 | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)

		if [ -z "$RESULTPID" ]; then
			echo 1
		else
			# Issue SIGTERM signal to process
			kill -15 $1
			# Wait 2 seconds to allow it to exit gracefully
			sleep 2
			# Check if process has exited
			RESULTPID=$($PS -o pid,user,command $1 | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)
			
			if [ -n "$RESULTPID" ]; then
				# If we reach here its generally because the program is ignoring
				# the SIGTERM we issued to it and/or its frozen

				# Issue SIGKILL signal to process
				kill -9 $1

				# Check if process has exited
				RESULTPID=$($PS -o pid,user,command $1 | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)

				if [ -n "$RESULTPID" ]; then
				    # Return 0 for fail
					echo 0
				else
				    # Return 1 for success
					echo 1
				fi
			else
				echo 1
			fi

		fi

	else
		echo 0
	fi

}

# Counts lines within PIDFILE excluding comments
function get_instance_count() {

    if [ -e $PIDFILE ]; then
        INSTANCE_COUNT=$($CAT $PIDFILE | $SED -n -e '/^#/!p' | $WC -l | $AWK '{{print $1}}')
    fi
    
    echo $INSTANCE_COUNT
}

function check_if_port_is_available() {

    if [ -n "$1" ]; then
        
        # Set sed params
        SEDPARAMS="/^tcp.*$1.*/p"
        
        # Run netstat command to determine if port is been used
        CHECKPORT=$($NETSTAT -ntl | $SED -n -e $SEDPARAMS)
        
        # Return 1 if port is free else 0
        if [ -z "$CHECKPORT" ]; then
            echo 1
        else
            echo 0
        fi
        
    else
        # Required argument: port number was not passed to the function
        exit 2
    fi
}

function find_free_port() {

    local FOUNDPORT=0
	local TEMPPORT=$PORT
	local RESULT=0
    
    while [ $FOUNDPORT -eq 0 ]
    do
        RESULT=$(check_if_port_is_available $TEMPPORT)

        if [ $RESULT -eq 1 ]; then
            FOUNDPORT=1
        else
            TEMPPORT=$((TEMPPORT + 1))
        fi
    done
    
    echo $TEMPPORT
}

function find_free_display() {

    local FOUNDDISPLAY=0
	local TEMPDISPLAY=$DISPLAY
    
    while [ $FOUNDDISPLAY -eq 0 ]
    do
		result=$($PS x -o pid,user,command | $GREP -i 'xvfb' | $GREP $TEMPDISPLAY )
        
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

function load_pids_from_pid_file() {

    unset SELENIUM_PIDLIST
    unset XVFB_PIDLIST
    
	if [ -e $PIDFILE ]; then

		for PIDITEM in $($CAT $PIDFILE | $SED -n -e '/^#/!p')
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

				if [ -n "$PIDITEM_SELENIUMPID" ]; then

                    if [ ${#SELENIUM_PIDLIST[@]} -eq 0 ]; then
                        # If the array has no items then add the first
                        SELENIUM_PIDLIST=("$PIDITEM_SELENIUMPID")
                    else
                        # If the array has items then add to the existing
                        SELENIUM_PIDLIST=("${SELENIUM_PIDLIST[@]}" "$PIDITEM_SELENIUMPID")
                    fi
				fi

				if [ -n "$PIDITEM_XVFBPID" -a -n "$PIDITEM_INSTANCENO" ]; then

                    if [ ${#XVFB_PIDLIST[@]} -eq 0 ]; then
                        # If the array has no items then add the first
                        XVFB_PIDLIST=("$PIDITEM_XVFBPID")
                    else
                        # If the array has items then add to the existing
                        XVFB_PIDLIST=("${XVFB_PIDLIST[@]}" "$PIDITEM_XVFBPID")
                    fi
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

    if [ -e $PIDFILE -a -n $1 ]; then

        # Prepare param for sed
        SEDPARAMS="/$1/d"
        
        # Remove PID from PIDFILE
        $SED -i $SEDPARAMS $PIDFILE

        # Prepare param for sed
        SEDPARAMS="/$1/p"

        # Check if has been removed from PID file
        RESULT=$($SED -n -e $SEDPARAMS $PIDFILE)

		if [ -z "$RESULT" ]; then
			echo 1
		else
			echo 0
		fi

    else
		echo 0
    fi
}

function verify_pids_in_pid_file() {

	load_pids_from_pid_file > /dev/null
	RESULT=$(load_pids_from_pid_file)
	local XCOUNT=0

	if [ $RESULT -eq 1 ]; then

		for ITEM_SELENIUM in "${SELENIUM_PIDLIST[@]}"
		do

			# Check if selenium process is running
			RESULT_SELENIUM_PID=$($PS x -o pid,user,command ${SELENIUM_PIDLIST[XCOUNT]} | $SED -n -e '/.*PID.*COMMAND/!p' | $HEAD -n 1)
			RESULT_XVBF_PID=$($PS x -o pid,user,command ${XVFB_PIDLIST[XCOUNT]} | $SED -n -e '/.*PID.*COMMAND/!p' | $HEAD -n 1)
			
			if [ -z "$RESULT_SELENIUM_PID" -o -z "$RESULT_XVBF_PID" ]; then

				if [ -n "$RESULT_SELENIUM_PID" ]; then

					kill_program $RESULT_SELENIUM_PID > /dev/null

				elif [ -n "$RESULT_XVBF_PID" ]; then

					kill_program $RESULT_XVBF_PID > /dev/null
					
				fi

				PARAMS=".*${SELENIUM_PIDLIST[$XCOUNT]},${XVFB_PIDLIST[$XCOUNT]}.*"
				RESULT=$(remove_pid $PARAMS)
				
			fi 

            XCOUNT=$((XCOUNT + 1))
		done
	else
	    # Return 0 if nothing was done
		echo 0
	fi
	
	# Refresh PID arrays
	load_pids_from_pid_file > /dev/null
	
	# Completed successfully
	echo 1
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

		if [ -e $PIDFILE ]; then
			echo 1
		else
			echo 0
		fi
	else
		echo 0
	fi
}

# Intended to check for the presence of a
# PID in the PID file
function check_pid() {

    if [ -n "$1" ]; then
    
        SEDPARAMS="/.*,$1,.*/p"
        RESULT=$($SED -n -e $SEDPARAMS $PIDFILE)
        
        if [ -z "$RESULT" ]; then
            # Return 0 if this PID is not found in the PID file
            echo 0
        else
            # Return 1 if this PID is found in the PID file
            echo 1
        fi
        
    else
        # Return 2 if no argument is passed to the function
        echo 2
    fi

}

function rewrite_pid_file() {

    local COUNT=0

    # Fetch array passed to the function
    local LINE_ARRAY=("$@")

    if [ ! -e $PIDFILE ]; then
        $TOUCH $PIDFILE
    fi

    if [ -e $PIDFILE ]; then

        # Clean up the instances in the PID file and reload the PIDs from the PID file
        verify_pids_in_pid_file > /dev/null

        if [ ${#SELENIUM_PIDLIST[@]} -ne 0 -a ${#XVFB_PIDLIST[@]} -ne 0 ]; then
            # Rewrite PID file and exclude comment line
            echo -e "PID file array: ${SELENIUM_PIDLIST[@]}"

            for LINE_ITEM in $($CAT $PIDFILE | $SED -n -e '/^#/!p')
            do

                # Increment count
                COUNT=$((COUNT + 1))
                echo "Count: $COUNT"

                # Setup SED params
                SEDPARAMS="s/^[0-9]\{1,2\},/$COUNT,/"

                # Change line instance #
                LINE_STRING=$(echo $LINE_ITEM | $SED $SEDPARAMS | $HEAD -n 1)
                
                # If adding first entry then write the comment line first
                if [ $COUNT -eq 1 ]; then
                    # Empty PID file
                    $CAT /dev/null > $PIDFILE

                    # Add comment line
                    echo "#ID,SELENIUMPID,XVFBPID,PORT,DISPLAY,PHPUNIT" >> $PIDFILE
                fi

                # Append file
                echo $LINE_STRING >> $PIDFILE

            done
 
        fi
        
    fi

    if [ ${#LINE_ARRAY[@]} -gt 0 ]; then

        for LINE_ITEM in ${LINE_ARRAY[@]}
        do
            VALIDATE_LINE_ITEM=$(echo $LINE_ITEM | $SED -n -e '/^[0-9]\{1,2\},[0-9]\{1,8\},[0-9]\{1,8\},[0-9]\{1,4\},[0-9]\{1,4\},[0-9]\{1,8\}/p')

            if [ -n $VALIDATE_LINE_ITEM ]; then
                echo $LINE_ITEM >> $PIDFILE
            fi

        done
        
        echo 1
    else
        echo 0
    fi

}

function add_pid_to_file() {

    # Save passed array argument to a local variable
    local ARRAY=("$@")

    # Must pass array as an argument to this function
	if [ ${#ARRAY[@]} -eq 4 ]; then
	
	    local _SELENIUM_PID=${ARRAY[0]}
	    local _XVFB_PID=${ARRAY[1]}
	    local _PORT=${ARRAY[2]}
	    local _DISPLAY=${ARRAY[3]}
	    local YCOUNT=$(get_instance_count)
        local COUNT=0
        declare local ARRAY
	    
	
    	if [ ! -e $PIDFILE ]; then
    	    create_pid_file > /dev/null
    	fi
    	
    	if [ -e $PIDFILE ]; then
    	
    	    # Check if the processes are running
			local RESULT_SELENIUM=$($PS x -o pid,user,command ${ARRAY[0]} | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)
			local RESULT_XVFB=$($PS x -o pid,user,command ${ARRAY[1]} | $SED -n -e '/.*PID.*COMMAND.*/!p' | $HEAD -n 1)

			if [ -n "$RESULT_SELENIUM" -a -n "$RESULT_XVFB" ]; then
			    
			    # Check for the presense of either of the following PIDs
                local RESULT_PID1=$(check_pid ${ARRAY[0]})
                local RESULT_PID2=$(check_pid ${ARRAY[1]})

                if [ $RESULT_PID1 -ne 0 -o $RESULT_PID2 -ne 0 ]; then

                    # Rescan PIDs in PID file and update accordingly
                    verify_pids_in_pid_file > /dev/null
                    YCOUNT=$(get_instance_count)
                    
			        # Recheck for the presense of either of the following PIDs
                    RESULT_PID1=$(check_pid ${ARRAY[0]})
                    RESULT_PID2=$(check_pid ${ARRAY[1]})
                    
                fi
                
                if [ $RESULT_PID1 -eq 0 -a $RESULT_PID2 -eq 0 ]; then
                    
                    YCOUNT=$((YCOUNT + 1))
                    
                    # Build string to append to PID file
                    _STRING_VALUE="$YCOUNT,$_SELENIUM_PID,$_XVFB_PID,$_PORT,$_DISPLAY,0"
                    
                    ARRAY=($_STRING_VALUE)


                    # Rewrite PID file with new instance
                    #RESULT=$(rewrite_pid_file ${ARRAY[@]})
                    rewrite_pid_file ${ARRAY[@]}

                    # Prepare SED params
                    SEDPARAMS="/$_STRING_VALUE/p"
                    
                    # Search presence of PID string in PID file
                    RESULT=$($SED -n -e $SEDPARAMS $PIDFILE)
                    
                    if [ -n "$RESULT" ]; then
                        echo 1
                    else
                        echo 0
                    fi
                fi
        		
			elif [ -z "$RESULT_SELENIUM" -o -z "$RESULT_XVFB" ]; then
			
                if [ -n "$RESULT_SELENIUM" ]; then
                    kill_program ${ARRAY[0]} > /dev/null
                elif [ -n "$RESULT_XVFB" ]; then
                    kill_program ${ARRAY[1]} > /dev/null
                fi
                # Clean up PID file
                verify_pids_in_pid_file > /dev/null
                # Kill any started applications and return 0
                echo 0
			fi
        else
            echo 0
        fi
    else
        # Return 2 if argument passed is not an array
        # with a count of 4 items
        echo 2
	fi
}

function get_pid_for_program() {
    
    # The function requires 1 argument to be passed to it.
    # The argument required is a regexp value to be used
    # to find the process using grep
    
    if [ -n "$1" ]; then
    
        # Run ps to and pipe out to grep to fetch a matching process
        PSQUERY=$($PS x -o user,pid,command | $GREP -Pi "$1" | $SED -n -e '/grep/!p')
        
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

function clear_all_instances() {

    clear && clear && clear
    echo -e "WARNING:"
    echo -e "Are you sure you want to kill all running selenium instances? [yN]"
    read

    if [[ "$REPLY" == [Yy]* ]]; then

        if [ ${#SELENIUM_PIDLIST[@]} -gt 0 -a ${#XVFB_PIDLIST[@]} -gt 0 ]; then
        
            local COUNT=${#SELENIUM_PIDLIST[@]}
            # Kill all Selenium processes
            for i in ${SELENIUM_PIDLIST[@]}
            do
                echo -e "Print process:"
                $PS x -o pid,user,command $i | $SED -n -e '/.*PID.*USER.*COMMAND/!p'

                echo -e "Killing process PID: $i"
                KILLSTATUS=$(kill_program $i)

                if [ $KILLSTATUS -eq 1 ]; then
                    echo -e "Process with PID terminated successfully: $i"
                else
                    echo -e "Process with PID failed to terminate: $i"
                fi

            done

            # Kill all XVFB processes
            COUNT=${#XVFB_PIDLIST[@]}
            for i in ${XVFB_PIDLIST[@]}
            do
                echo -e "Print process:"
                $PS x -o pid,user,command $i | $SED -n -e '/.*PID.*USER.*COMMAND/!p'

                echo -e "Killing process PID: $i"
                KILLSTATUS=$(kill_program $i)

                if [ $KILLSTATUS -eq 1 ]; then
                    echo -e "Process with PID terminated successfully: $i"
                else
                    echo -e "Process with PID failed to terminate: $i"
                fi

            done

            # Clear arrays for PID lists
            unset SELENIUM_PIDLIST
            unset XVFB_PIDLIST
            
            # Clear the content of the PID file
            if [ -e $PIDFILE ]; then
                $CAT /dev/null > $PIDFILE
                # Add comment line
                echo "#ID,SELENIUMPID,XVFBPID,PORT,DISPLAY,PHPUNIT" >> $PIDFILE
            fi

        fi
    fi
}

function run_instance() {

	local FREE_PORT=$(find_free_port)
	local FREE_DISPLAY=$(find_free_display)
	local NEW_XVFB_LOGFILE=$HOME/.xvfb_$FREE_PORT.log
	local NEW_SELENIUM_LOGFILE=$HOME/.selenium_$FREE_PORT.log

	$XVFB :$FREE_DISPLAY -ac -screen 0 $XVFBSCREENRES > $NEW_XVFB_LOGFILE 2>&1 &
	export DISPLAY=localhost:$FREE_DISPLAY.0
	$JAVA -jar $SELENIUM -port $FREE_PORT > $NEW_SELENIUM_LOGFILE 2>&1 &

	GREP_PARAMS="$XVFB.*$FREE_DISPLAY"
	local NEW_XVFB_PID=$(get_pid_for_program $GREP_PARAMS)
	GREP_PARAMS="$SELENIUM.*$FREE_PORT"
	local NEW_SELENIUM_PID=$(get_pid_for_program $GREP_PARAMS)
	echo -e "Selenium PID: $NEW_SELENIUM_PID"
	echo -e "XVFB PID: $NEW_XVFB_PID"
	
	if [ -n "$NEW_XVFB_PID" -a -n "$NEW_SELENIUM_PID" ]; then
        
		# Create PID file if it does not exist
		create_pid_file > /dev/null

		# Clean up PID file
		verify_pids_in_pid_file > /dev/null
		
		# Setup array with data to add for this instance to the PID file
		# Tested this array is working correctly
		local PID_DATA=($NEW_SELENIUM_PID $NEW_XVFB_PID $FREE_PORT $FREE_DISPLAY)
		
		# Attempt to add data to the PID file
		RESULT=$(add_pid_to_file ${PID_DATA[@]})
        echo -e $RESULT
        exit 1
		
		if [ $RESULT -eq 0 -o $RESULT -eq 2 ]; then
            kill_program $NEW_XVFB_PID > /dev/null
            kill_program $NEW_SELENIUM_PID > /dev/null
            echo 0
        elif [ $RESULT -eq 1 ]; then
            echo 1
		fi
		
	elif [ -z "$NEW_XVFB_PID" -o -z "$NEW_SELENIUM_PID" ]; then
	
	    # if one of the two processes started as the instance
	    # fails to start then kill both processes
	    kill_program $NEW_XVFB_PID > /dev/null
	    kill_program $NEW_SELENIUM_PID > /dev/null
	    echo 0
	fi
	
}

# This function is intended to stop an instance
# and remove its presence from the PID file
function remove_instance() {
    local SOME_VAR="TEST"
}

function list_instances() {
    local SOME_VAR="TEST"
}

# Intended to fetch an instance status
# for the purpose determining if it is
# free or busy
function get_instance_status() {
    local SOME_VAR="TEST"
}

# This function is intended to find unmanaged instances of
# selenium and and add it to the PID file to be managed
function find_unmanaged_instances() {
	local SOME_VAR="TEST"
	# Add some code here
}

function run_automation_fand_rollover() {
    local INSTANCE=0
    local INSTANCE_SELENIUM_PID=0
    local INSTANCE_XVFB_PID=0
    local SCRIPT_LOG_FILE=0
    run_instance
    
    # Fetch the newly created instance
    if [ ${#SELENIUM_PIDLIST[@]} -ne 0 ]; then
        INSTANCE=${#SELENIUM_PIDLIST[@]}
        INSTANCE=$((INSTANCE - 1))
        INSTANCE_SELENIUM_PID=${SELENIUM_PIDLIST[INSTANCE]}
        INSTANCE_XVFB_PID=${XVFB_PIDLIST[INSTANCE]}
        echo -e "Print instance picked for FANDV"
        exit 0
        # Run the script
        if [ -n $1 ]; then
            if [ -r $1 ]; then
                if [ -r $PIDFILE ]; then
                    
                    SEDPARAMS="/$INSTANCE,$INSTANCE_SELENIUM_PID,$INSTANCE_XVFB_PID,.*/p"
                    INSTANCE_PORT=$($SED -n -e $SEDPARAMS | $AWK '{print $4}')
                    if [ -e $SCRIPT_FANDV -a -x $SCRIPT_FANDV ]; then
                        SCRIPT_LOG_FILE="$HOME/.PHPUNIT_FANDV_SCRIPT_$INSTANCE_PORT.log"

                        # Set environment variables
                        export FANDV_ROLLOVER_FILE=$1

                        $PHPUNIT $SCRIPT_FANDV > $SCRIPT_LOG_FILE 2>&1 &
                        PROCESS_LOOKUP_STRING="phpunit.*$SCRIPT_FANDV.*$SCRIPT_FILE"

                        if [ -n $PROCESS_LOOKUP_STRING ]; then
                            IS_RUNNING=$($PS x -o pid,user,command | $SED -n -e '/.*PID.*USER.*COMMAND/!p' | $GREP -Pi $PROCESS_LOOKUP_STRING)
                            if [ -n $IS_RUNNING ]; then
                                echo -e "Successfully started F and V Rollover on selenium instance: $INSTANCE"
                                echo -e "PHPUnit log file: $SCRIPT_LOG_FILE"
                            fi
                        else
                            echo -e "Failed to start FandV Rollover script"
                        fi
                        
                    else
                        echo -e "Could not the F and V Script to run at set location:"
                        echo -e "$SCRIPT_FANDV"
                        echo -e "Please set the correct location of the script in the following config file:"
                        echo -e "$CONFIG_FILE"
                        exit 1
                    fi
                fi
            else
                clear && clear && clear
                echo -e "ERROR:"
                echo -e "Could not find the argument supplied XLS file: $1"
                echo -e "Please verify and/or correct its location"
            fi
        fi
    fi
}

function run_automation_ncp_updates() {
    local SOME_VAR="TEST"
}

function print_usage() {
    clear && clear && clear
    echo -e "Automation Manager: A bash script to manage headless selenium server instances"
    echo -e ""
    echo -e "Usage: automation-manager.sh <start|stop|status|list-selenium|set-selenium|run>"
    echo -e ""
    echo -e "COMMAND SYNTAX LEGEND:"
    echo -e "<> - required argument"
    echo -e "[] - optional argument"
    echo -e ""
    echo -e "start:                             start a new selenium server instance"
    echo -e "stop <instance>:                   stop a selenium server instance"
    echo -e "stop-all:                          stop all selenium server instances. Be careful with this!"
    echo -e "status [instance]:                 status of all instances or a particular instance"
    echo -e "list-selenium:                     find and list all selenium server files on your system"
    echo -e "                                   as well as show the set default selenium file"
    echo -e "set-selenium:                      find and list all selenium server files and set the default"
    echo -e ""
    echo -e "run <fandv-rollover|ncp-rate-update> <xlsfile>"
    echo -e "                                   Find existing or start new selenium instance and run requested"
    echo -e "                                   automation process. Pick between the following processes:"
    echo -e "fandv-rollover <xlsfile>:          F and V monthly rollover automation process"
    echo -e "ncp-rate-update <xlsfile>:         NCP monthly rate update automation process"
}

# Clean up PIDs in the PID file
verify_pids_in_pid_file

case "$1" in
start)
    check_requirements
    run_instance
    ;;
stop)
    check_requirements
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
stop-all)
    clear_all_instances
    ;;
status)
    check_requirements
    if [ -e $PIDFILE ]; then
        clear && clear && clear
        echo -e "Currently managed selenium instances:"
        $CAT $PIDFILE
    else
        echo -e "There is no selenium instances managed at this moment."
    fi
    ;;
run)
    if [ $# -eq 3 ]; then
        case "$2" in
        fandv-rollover)
            echo -e "Attempting to run F and V contracts rollover script..."
            run_automation_fand_rollover $3
            ;;
        ncp-rate-update)
            echo -e "Attempting to run NCP rates update script..."
            ;;
        *)
            print_usage
            echo -e "ERROR: Invalid argument given for run option"
        esac
    else
        print_usage
        echo "ERROR: Not all required arguments where supplied"
    fi
    ;;
list-selenium)
    listselenium
    if [ -e $SELENIUM ]; then
        echo -e "DEFAULT SELENIUM: $SELENIUM"
    else
        clear && clear && clear
        echo -e "ERROR:"
        echo -e "The DEFAULT set selenium was not found: $SELENIUM"
        set_default_selenium
    fi
    ;;
set-selenium)
    set_default_selenium
    ;;
*)
    print_usage
    exit 1
esac
exit 0
