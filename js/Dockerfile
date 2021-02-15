FROM node:lts-alpine3.12 as node_assets

RUN mkdir -p /home/node/app
WORKDIR /home/node/app
COPY package.json package.json
RUN apk --no-cache --virtual build-dependencies add \
    python3 \
    make \
    g++ \
    && npm install

FROM node_assets as node_app
CMD ["npm", "start"]