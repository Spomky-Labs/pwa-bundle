'use strict';

export const computeJwkThumbprint = async (jwk) => {
    const canonical = {
        crv: jwk.crv,
        kty: jwk.kty,
        x: jwk.x,
        y: jwk.y
    };

    const encoder = new TextEncoder();
    const data = encoder.encode(JSON.stringify(canonical));
    const hash = await crypto.subtle.digest('SHA-256', data);
    const b64 = btoa(String.fromCharCode(...new Uint8Array(hash)));
    return b64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
};
