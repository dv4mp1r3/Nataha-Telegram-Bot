FROM node:18-alpine3.14 as node_assets

RUN mkdir -p /home/node/app/audio
COPY ./js /home/node/app
WORKDIR /home/node/app

RUN apk --no-cache --virtual build-dependencies add \
    python3 \
    make \
    g++ \
    && chown -R node:node /home/node/app && npm install

FROM node_assets as node_app
USER node
CMD ["npm", "start"]