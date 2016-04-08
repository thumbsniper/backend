#!/bin/bash

if test -z "$MONGO_HOST" || test -z "$MONGO_PORT" || test -z "$MONGO_USER" || test -z "$MONGO_PASS" || test -z "$MONGO_DB"; then
	echo "environment variable(s) missing"
	exit 1
fi



### Wait for MongoDB

until nc -z $MONGO_HOST $MONGO_PORT
do
	echo "waiting for MongoDB"
	sleep 1
done


### Initialize MongoDB

mongo --host $MONGO_HOST --port $MONGO_PORT --eval "
	db = db.getSiblingDB(\"${MONGO_DB}\");
	db.createUser({
		user: \"${MONGO_USER}\",
		pwd: \"${MONGO_PASS}\",
		roles: [ 
			'readWrite'
		]})"



### Finally: run Apache in foreground

apache2-foreground

