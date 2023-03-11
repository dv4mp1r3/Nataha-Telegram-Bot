FROM node:18-alpine3.14 as node_assets

RUN mkdir -p /home/node/app
WORKDIR /home/node/app
<<<<<<<< HEAD:configs/node.dockerfile
COPY ./js/package.json ./js/package-lock.json ./
========
COPY ./js/package.json package.json
COPY ./js/package-lock.json package-lock.json
>>>>>>>> 1407b20dc89646ef88f32253e567eb60c1384393:node.dockerfile
RUN apk --no-cache --virtual build-dependencies add \
    python3 \
    make \
    g++ \
    && npm install

FROM node_assets as node_app
CMD ["npm", "start"]