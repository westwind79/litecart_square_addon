FROM alpine:3.15.4

RUN apk update && apk upgrade

RUN chmod -R 755 /app