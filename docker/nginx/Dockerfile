FROM nginx:1.20.1-alpine

ADD ./hosts/event_bot.conf /etc/nginx/conf.d/event_bot.conf

WORKDIR /data/event_bot

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]