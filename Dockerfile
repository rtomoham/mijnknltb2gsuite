# Use phusion/baseimage as base image. To make your builds
# reproducible, make sure you lock down to a specific version, not
# to `latest`! See
# https://github.com/phusion/baseimage-docker/blob/master/Changelog.md
# for a list of version numbers.
FROM phusion/baseimage:master-amd64

ARG PROGRAM_NAME=mijnknltb2gsuite
ARG HELPER_NAME=crontabmanager4mijnknltb2gsuite

# Use baseimage-docker's init system.
CMD ["/sbin/my_init"]

# ...put your own build instructions here...

ENV TZ=Europe/Amsterdam
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt update
RUN apt install php-cli php-curl php-dom composer unzip wget -y && \
	mkdir /${PROGRAM_NAME} && \
	cd /${PROGRAM_NAME} && \
	composer require google/apiclient:^2.0
# Add some random number, to ensure we always pull the latest version of the code
ADD "https://www.random.org/cgi-bin/randbyte?nbytes=10&format=h" skipcache
RUN cd / && \
	wget https://github.com/rtomoham/${HELPER_NAME}/archive/master.zip && \
	unzip master.zip && \
	mv /${HELPER_NAME}-master/src/* /${PROGRAM_NAME} && \
	rm -rf /${HELPER_NAME}-master && \
	rm master.zip
RUN cd / && \
	wget https://github.com/rtomoham/${PROGRAM_NAME}/archive/master.zip && \
	unzip master.zip && \
	mv /${PROGRAM_NAME}-master/* /${PROGRAM_NAME} && \
	chmod +x /${PROGRAM_NAME}/run && \
	rmdir /${PROGRAM_NAME}-master && \
	rm master.zip
#	cd /${PROGRAM_NAME} && \
#	/usr/bin/php CrontabManager.php

# Clean up APT when done.
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

#ENTRYPOINT /mijnknltb2gsuite/run