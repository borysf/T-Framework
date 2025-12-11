### Requirements

* Docker

### Build image

`docker build . --build-arg target=development -t tf`

### Run container

`docker run -it --rm -v ./www:/var/www/html -p 80:8080 tf`

### Access apps

* Documentation: http://localhost/__docs/
* Php info: http://localhost/__info/
* Test app: http://localhost/test/

To access logs, simply open console in your browser