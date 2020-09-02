# Use phusion/baseimage as base image. To make your builds
# reproducible, make sure you lock down to a specific version, not
# to `latest`! See
# https://github.com/phusion/baseimage-docker/blob/master/Changelog.md
# for a list of version numbers.
FROM phusion/baseimage:master-amd64

ARG PROGRAM_NAME=mijnknltb2gsuite
ARG HELPER_NAME=mycrontabmanager

# Use baseimage-docker's init system.
CMD ["/sbin/my_init"]

# ...put your own build instructions here...
#ENV TZ=Europe/Amsterdam
#RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt update
RUN apt install php-cli php-curl php-dom composer unzip wget -y && \
	mkdir /etc/${PROGRAM_NAME} && \
	cd /etc/${PROGRAM_NAME} && \
	composer require google/apiclient:^2.0
# Add some random number, to ensure we always pull the latest version of the code
#ADD "https://www.random.org/cgi-bin/randbyte?nbytes=10&format=h" skipcache
ADD "http://random.tomohamat.com/" skipcache
RUN cd /etc && \
	wget https://github.com/rtomoham/${HELPER_NAME}/archive/${PROGRAM_NAME}.zip && \
	unzip -j ${PROGRAM_NAME}.zip */src/* -d ${PROGRAM_NAME}
RUN cd /etc && \
	wget https://github.com/rtomoham/${PROGRAM_NAME}/archive/master.zip && \
	unzip -j master.zip -d ${PROGRAM_NAME} && \
	chmod +x /etc/${PROGRAM_NAME}/init
#	cd /${PROGRAM_NAME} && \
#	/usr/bin/php CrontabManager.php

# Clean up APT when done.
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /etc/master.zip /etc/${HELPER_NAME}.zip

ENTRYPOINT /etc/mijnknltb2gsuite/init