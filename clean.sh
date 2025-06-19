#!/bin/bash
# Удаляем все скоррелированные события
source /opt/zbx/cfg/env

# Удаление скоррелированных событий реализовано через триггер PostgreSQL
#query=$(psql -t $db_url <<EOF
#delete from events where eventid in (select re.r_eventid "id"
#from
#    events e
#    join event_recovery re on re.eventid = e.eventid
#where
#    re.c_eventid IS NOT NULL  and re.r_eventid IS NOT NULL
#UNION
#select re.eventid "id"
#from
#    events e
#    join event_recovery re on re.eventid = e.eventid
#where
#    re.c_eventid IS NOT NULL  and re.r_eventid IS NOT NULL)
#EOF
#)
#echo $query

# Закрываем все подтвержденные события с именем %[#Manual]

query=$(psql -t $db_url <<EOF
select eventid from problem where
name like '%[#Manual]%'
and r_eventid is null
and acknowledged = 1
order by clock desc
EOF
)
echo $query
events=$(echo $query | sed 's/ /","/g')

echo $(curl -k -s -X POST -H 'Content-Type: application/json-rpc' -d '{ "jsonrpc": "2.0",
	"method": "event.acknowledge",
	"params": {
		 "eventids":  ["'$events'"],
		  "action": 5,
		 "message":"Закрытие подтвержденного события с тегом [#Manual]"
	},
    "id": 12,
    "auth": "'$key'"}' $api_url  | jq '.result'   )
