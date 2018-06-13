var app = require('express')();
var server = require('http').Server(app);
var io = require('socket.io')(server);
var debug = require('debug')('SmartCar:sockets');
var request = require('request');
var port = process.env.PORT || '3500';

// Sender will user_id and receiver will provider_id
server.listen(port);

io.on('connection', function (socket) {

    debug('new connection established');
    debug('socket.handshake.query.sender', socket.handshake.query.sender);

    socket.join(socket.handshake.query.sender);

    socket.emit('connected', 'Connection to server established!');

    socket.on('update sender', function(data) {
        console.log('update sender', data);
        socket.join(data.sender);
        socket.handshake.query.sender=data.sender;
        socket.emit('sender updated', 'Sender Updated ID:'+data.sender);
    });

    socket.on('send message', function(data) {
        console.log(data);
        data.sender = socket.handshake.query.sender;
        data.time = new Date();
        socket.broadcast.to(data.receiver).emit('message', data);
        // console.log(socket.broadcast.to(data.receiver).emit('message', data));

        request('http://67.209.127.246/taxiapp/public/location_update_trip?sender='+data.sender+'&receiver='+data.receiver+'&latitude='+data.latitude+'&longitude='+data.longitude+'&status='+data.status+'&request_id='+data.request_id, function (error, response, body) {
            console.log('http://67.209.127.246/taxiapp/public/location_update_trip?sender='+data.sender+'&receiver='+data.receiver+'&latitude='+data.latitude+'&longitude='+data.longitude+'&status='+data.status+'&request_id='+data.request_id);
            console.log(body)
            // if (error && response.statusCode != 200) {
            //     console.log(body) // Show the HTML for the Google homepage.
            // }
        });
    });

    socket.on('disconnect', function(data) {
        debug('disconnect', data);
    });
});
