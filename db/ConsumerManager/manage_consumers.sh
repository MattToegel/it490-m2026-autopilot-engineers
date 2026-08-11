#!/bin/bash
# manage-consumers.sh
# tad46: Interactive management CLI for DB VM consumers (tmux-backed)
# tad46: The CLI owns ONE tmux session; each consumer runs in its own pane.
# tad46: "View live" attaches to that session, so you see the real consumer
# tad46: terminals streaming - not a copy. Each pane also tees to a log file
# tad46: so crash stack traces persist after the session is gone.
#
# tad46: SHORTCUTS - pass menu numbers as arguments to run them up front,
# tad46: then drop into the menu. Examples:
# tad46:   manage-consumers.sh 1       -> start all, then show menu
# tad46:   manage-consumers.sh 1 5     -> start all, then attach to dashboard
# tad46:   manage-consumers.sh 1 3     -> start all, then print status

SESSION="otr-consumers"
DB_ROOT="$HOME/it490-m2026-autopilot-engineers/db"
LOG_DIR="$HOME/it490-m2026-autopilot-engineers/db/ConsumerManager/consumer_logs"
mkdir -p "$LOG_DIR"

# tad46: Ordered list drives pane creation AND pane indexing.
# tad46: Pane index = position in this array (auth=0, logs=1, flights=2).
# tad46: Add new consumers here and they slot in as the next pane.
# cao39: Added admin consumer - US-04
NAMES=(auth logs flights admin alerts reports)

declare -A CONSUMERS=(
    ["auth"]="auth/auth_consumer.php"
    ["logs"]="logging/logs_consumer.php"
    ["flights"]="flights/flights_consumer.php"
    # cao39: Added the admin consumer - US-04
    ["admin"]="admin/admin_consumer.php"
    ["alerts"]="/flights/alerts/alerts_consumer.php"
    ["reports"]="/reports/reports_consumer.php"
)

# Helpers

# tad46: Builds the shell command for a consumer: run it, merge stderr into
# tad46: stdout, and tee to a persistent log while still showing in the pane.
launch_cmd()
{
    local name=$1
    local script="$DB_ROOT/${CONSUMERS[$name]}"
    echo "php \"$script\" 2>&1 | tee -a \"$LOG_DIR/$name.log\""
}

session_exists()
{
    tmux has-session -t "$SESSION" 2>/dev/null
}

start_all()
{
    if session_exists; then
        echo "  Session '$SESSION' already running. Use 'Restart one' or 'Stop all'."
        return
    fi

    # tad46: Logs persist across sessions via tee -a, so starting fresh is a
    # tad46: choice, not a default - ask before wiping the previous session's
    # tad46: captured logs. Keeping them means new output appends after old.
    # tad46: (Only affects the tee'd manager logs, NOT db_listener.log or the
    # tad46: MySQL logs table.)
    local n
    read -p "  Clear previous consumer .log files before starting? (y/N) " wipe
    if [ "$wipe" = "y" ] || [ "$wipe" = "Y" ]; then
        for n in "${NAMES[@]}"; do
            : > "$LOG_DIR/$n.log"
        done
        echo "  Old logs cleared."
    else
        echo "  Keeping old logs - new output will append."
    fi

    # tad46: First consumer creates the session (pane 0), rest split off it.
    local first="${NAMES[0]}"
    tmux new-session -d -s "$SESSION" -n consumers -c "$DB_ROOT"
    tmux set-window-option -t "$SESSION" pane-base-index 0
    # tad46: Keep a pane open after its process dies so a crash shows its
    # tad46: stack trace instead of the pane silently vanishing.
    tmux set-window-option -t "$SESSION" remain-on-exit on

    tmux send-keys -t "$SESSION:0.0" "$(launch_cmd "$first")" C-m

    local i
    for i in "${!NAMES[@]}"; do
        [ "$i" -eq 0 ] && continue
        local name="${NAMES[$i]}"
        tmux select-layout -t "$SESSION" tiled   # tad46: re-tile BEFORE each split so there's always room
        tmux split-window -v -t "$SESSION" -c "$DB_ROOT"
        tmux send-keys -t "$SESSION:0.$i" "$(launch_cmd "$name")" C-m
    done

    tmux select-layout -t "$SESSION" tiled
    echo "  Started ${#NAMES[@]} consumers in '$SESSION'."
    echo "  Choose 'View live dashboard' to watch them."
}

stop_all()
{
    if session_exists; then
        tmux kill-session -t "$SESSION"
        echo "  Stopped and cleared session '$SESSION'."
    else
        echo "  Nothing running."
    fi
}

# tad46: Restart a single consumer in place - useful after patching one file
# tad46: (e.g. flights crashes, you fix it, restart just that pane) without
# tad46: touching the others. respawn-pane -k reuses the same pane slot.
# tad46: Note: uses tee -a, so a restart appends to the current session log
# tad46: rather than wiping it - you keep the pre-crash trace plus new output.
restart_one()
{
    if ! session_exists; then
        echo "  No session running. Use 'Start all' first."
        return
    fi

    echo "  Available: ${NAMES[*]}"
    read -p "  Which consumer to restart? " name

    local idx=-1 i
    for i in "${!NAMES[@]}"; do
        [ "${NAMES[$i]}" = "$name" ] && idx=$i && break
    done

    if [ "$idx" -lt 0 ]; then
        echo "  Unknown consumer: $name"
        return
    fi

    tmux respawn-pane -k -t "$SESSION:0.$idx" "$(launch_cmd "$name")"
    echo "  Restarted [$name] (pane $idx)."
}

status_all()
{
    if ! session_exists; then
        echo "  Session '$SESSION' not running - all consumers stopped."
        return
    fi

    echo "  Consumer status:"
    local i
    for i in "${!NAMES[@]}"; do
        local name="${NAMES[$i]}"
        local dead
        dead=$(tmux display-message -p -t "$SESSION:0.$i" '#{pane_dead}' 2>/dev/null)
        if [ "$dead" = "1" ]; then
            echo "    [$name] CRASHED (pane $i held open - check its trace)"
        elif [ "$dead" = "0" ]; then
            echo "    [$name] running (pane $i)"
        else
            echo "    [$name] no pane (not started?)"
        fi
    done
}

# tad46: Attach to the live session. Detach with Ctrl-b then d to come back
# tad46: to this menu. Guards against nesting if the CLI itself runs in tmux.
view_live()
{
    if ! session_exists; then
        echo "  No session running. Use 'Start all' first."
        return
    fi

    echo "  Attaching - press Ctrl-b then d to detach and return to the menu."
    sleep 1
    if [ -n "$TMUX" ]; then
        tmux switch-client -t "$SESSION"
    else
        tmux attach-session -t "$SESSION"
    fi
}

view_saved_log()
{
    echo "  Available: ${NAMES[*]}"
    read -p "  Which saved log to tail? " name

    if [ -f "$LOG_DIR/$name.log" ]; then
        echo "  Tailing $name.log - Ctrl-c to stop (consumers keep running)."
        sleep 1
        tail -f "$LOG_DIR/$name.log"
    else
        echo "  No saved log for '$name' yet."
    fi
}

clear_logs()
{
    read -p "  Really clear all saved log files? (y/N) " confirm
    if [ "$confirm" = "y" ] || [ "$confirm" = "Y" ]; then
        rm -f "$LOG_DIR"/*.log
        echo "  Saved logs cleared."
    fi
}

# ----- Menu -----

show_menu()
{
    echo ""
    echo "=== DB VM Consumer Manager (tmux) ==="
    echo "1) Start all consumers (dashboard)"
    echo "2) Stop all consumers"
    echo "3) Status"
    echo "4) Restart one consumer"
    echo "5) View live dashboard (attach)"
    echo "6) View saved log (specific)"
    echo "7) Clear saved logs"
    echo "q) Quit"
    echo ""
}

# tad46: Runs a single choice. Clears the screen first so the result of your
# tad46: pick starts on a clean screen instead of scrolling under old output.
# tad46: Used by both the interactive menu and the number-argument shortcuts.
handle_choice()
{
    local choice=$1
    clear 2>/dev/null

    case $choice in
        1) start_all ;;
        2) stop_all ;;
        3) status_all ;;
        4) restart_one ;;
        5) view_live ;;
        6) view_saved_log ;;
        7) clear_logs ;;
        q|Q) echo "Goodbye."; exit 0 ;;
        *) echo "  Unknown option: '$choice'" ;;
    esac
}

# ----- Main -----

if ! command -v tmux >/dev/null 2>&1; then
    echo "tmux is not installed. Run: sudo apt-get install -y tmux"
    exit 1
fi

echo "DB VM Consumer Manager - session '$SESSION', logs in $LOG_DIR"

# tad46: Any numbers/letters passed as arguments run first, in order, as if
# tad46: you had typed them at the menu. Then we fall into the interactive loop.
if [ "$#" -gt 0 ]; then
    for arg in "$@"; do
        handle_choice "$arg"
    done
fi

while true
do
    show_menu
    read -p "Choose: " choice
    handle_choice "$choice"
done
