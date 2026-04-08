#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DATADIR="$ROOT/database/mysql_local_data"
PORT=3307
SOCKET="$DATADIR/mysql.sock"
PIDFILE="$DATADIR/mysqld.pid"

MYSQL_HOME=""
for candidate in \
	/usr/local/mysql-9.4.0-macos15-arm64 \
	/usr/local/mysql; do
	if [[ -x "$candidate/bin/mysqld" ]]; then
		MYSQL_HOME="$candidate"
		break
	fi
done
if [[ -z "$MYSQL_HOME" ]]; then
	echo "Could not find mysqld under /usr/local/mysql*. Install MySQL or set MYSQL_HOME." >&2
	exit 1
fi

mkdir -p "$DATADIR"

if [[ ! -f "$DATADIR/ibdata1" ]]; then
	echo "Initializing MySQL datadir (--initialize-insecure)…" >&2
	"$MYSQL_HOME/bin/mysqld" --no-defaults --initialize-insecure --datadir="$DATADIR"
fi

if [[ -f "$PIDFILE" ]] && kill -0 "$(cat "$PIDFILE")" 2>/dev/null; then
	if "$MYSQL_HOME/bin/mysqladmin" -h 127.0.0.1 -P "$PORT" -u root ping --silent 2>/dev/null; then
		echo "Local MySQL already running on 127.0.0.1:$PORT"
		exit 0
	fi
fi

# Avoid mysqlx (33060) clashes with another server on the machine
nohup "$MYSQL_HOME/bin/mysqld" --no-defaults \
	--datadir="$DATADIR" \
	--port="$PORT" \
	--socket="$SOCKET" \
	--bind-address=127.0.0.1 \
	--mysqlx-port=33062 \
	--pid-file="$PIDFILE" \
	>>"$DATADIR/mysqld.log" 2>&1 &

for _ in $(seq 1 60); do
	if "$MYSQL_HOME/bin/mysqladmin" -h 127.0.0.1 -P "$PORT" -u root ping --silent 2>/dev/null; then
		echo "Local MySQL ready on 127.0.0.1:$PORT (datadir: $DATADIR)"
		if ! "$MYSQL_HOME/bin/mysql" -h 127.0.0.1 -P "$PORT" -u homestead --password=secret -N -e "SELECT 1 FROM configuration LIMIT 1" homestead &>/dev/null; then
			echo "" >&2
			echo "One-time: create app user + import data —" >&2
			echo "  $MYSQL_HOME/bin/mysql -h 127.0.0.1 -P $PORT -u root <\"$ROOT/database/setup/homestead-local.sql\"" >&2
			echo "  $MYSQL_HOME/bin/mysql -h 127.0.0.1 -P $PORT -u homestead --password=secret homestead <\"$ROOT/db_rentasuit_php.sql\"" >&2
		fi
		exit 0
	fi
	sleep 0.25
done

echo "MySQL did not become ready. See $DATADIR/mysqld.log" >&2
tail -30 "$DATADIR/mysqld.log" >&2 || true
exit 1
