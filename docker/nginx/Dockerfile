FROM nginx:1.20.1-alpine

ADD hosts/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /data/event_bot

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]