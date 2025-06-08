vcl 4.0;

backend default {
    .host = "nginx";
    .port = "80";
}

sub vcl_recv {
    if (req.method == "PURGE") {
        return (purge);
    }

    if (req.method != "GET" &&
        req.method != "HEAD" &&
        req.method != "PUT" &&
        req.method != "POST" &&
        req.method != "TRACE" &&
        req.method != "OPTIONS" &&
        req.method != "DELETE") {
        return (pipe);
    }

    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    if (req.url ~ "\.(css|js|jpg|jpeg|png|gif|ico|swf|woff|woff2|ttf|eot)$") {
        return (hash);
    }

    if (req.url ~ "^/admin") {
        return (pass);
    }

    if (req.url ~ "^/customer") {
        return (pass);
    }

    if (req.url ~ "^/checkout") {
        return (pass);
    }

    if (req.url ~ "^/cart") {
        return (pass);
    }

    if (req.url ~ "^/wishlist") {
        return (pass);
    }

    if (req.url ~ "^/api") {
        return (pass);
    }

    return (hash);
}

sub vcl_hash {
    hash_data(req.url);
    if (req.http.host) {
        hash_data(req.http.host);
    } else {
        hash_data(server.ip);
    }
    return (lookup);
}

sub vcl_hit {
    if (obj.ttl >= 0s) {
        return (deliver);
    }
    if (obj.ttl + obj.grace > 0s) {
        return (deliver);
    }
    return (miss);
}

sub vcl_backend_response {
    if (beresp.ttl <= 0s ||
        beresp.http.Set-Cookie ||
        beresp.http.Vary == "*") {
        set beresp.ttl = 120s;
        set beresp.uncacheable = true;
        return (deliver);
    }

    if (beresp.status == 200 || beresp.status == 204) {
        set beresp.ttl = 1h;
    }
} 