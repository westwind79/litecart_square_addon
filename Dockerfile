FROM alpine:3.15.4

RUN apk update && apk upgrade && apk add bash

RUN chmod -R 755 /app