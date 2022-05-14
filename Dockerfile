FROM alpine:3.15.4

RUN apt-get update && apt-get install -y

RUN chmod -R 755 /app