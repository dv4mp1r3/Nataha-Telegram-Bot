FROM node:18-alpine3.14 as node_assets

RUN mkdir -p /home/node/app
WORKDIR /home/node/app
COPY ./js/package.json ./js/discord.js ./
#COPY discord.js discord.js
RUN apk --no-cache --virtual build-dependencies add \
    python3 \
    make \
    g++ \
    && npm install

FROM node_assets as node_app

USER node

CMD ["npm", "start"]