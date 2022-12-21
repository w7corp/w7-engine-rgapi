FROM ccr.ccs.tencentyun.com/w7team/swoole:fpm-php7.2
MAINTAINER yuanwentao <admin@w7.com>

ENV WEB_PATH /home/WeEngine
ADD . $WEB_PATH
ADD ./WeEngine.conf /usr/local/nginx/conf/vhost/

WORKDIR $WEB_PATH

RUN echo '#!/bin/sh' >> start.sh \
    && echo "nginx" >> start.sh \
    && echo "php-fpm" >> start.sh \
    && echo "tail -f /dev/null" >> start.sh
CMD ["sh", "start.sh"]

RUN rm -rf Dockerfile .git \
    && chown -R 1000:1000 $WEB_PATH \
    && chmod -R 755 $WEB_PATH
