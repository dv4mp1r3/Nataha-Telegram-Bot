FROM node:18-alpine3.14 as node_assets

RUN mkdir -p /home/node/app
WORKDIR /home/node/app
COPY ./js/package.json ./js/package-lock.json ./
RUN apk --no-cache --virtual build-dependencies add \
    python3 \
    make \
    g++ \
    && npm install && npm run build

FROM node:18-alpine3.14 as node_dev
WORKDIR /home/node/app
COPY --from=node_assets --chown=node:node /home/node/app/node_modules ./
CMD ["npm", "run", "dev"]

FROM node:18-alpine3.14 as node_prod
WORKDIR /home/node/app
COPY --from=node_assets --chown=node:node /home/node/app/node_modules/ffmpeg-static/ffmpeg ./
COPY --from=node_assets --chown=node:node /home/node/app/node_modules/libsodium-wrappers ./node_modules/libsodium-wrappers
COPY --from=node_assets --chown=node:node /home/node/app/node_modules/libsodium ./node_modules/libsodium
COPY --from=node_assets --chown=node:node /home/node/app/app.js /home/node/app/package.json ./
USER node
CMD ["npm", "start"]