'use strict';

import { get } from 'idb-keyval';

const computeJwkThumbprint = async (jwk) => {
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
}

const base64url = (input) =>
    btoa(typeof input === 'string' ? input : JSON.stringify(input))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

export const signJWT = async (keyIndex) => {
    const keyData = await get(`auth-key:${keyIndex}`);
    if (!keyData || !keyData.privateJwk) return null;

    const privateKey = await crypto.subtle.importKey(
        'jwk',
        keyData.privateJwk,
        { name: 'ECDSA', namedCurve: 'P-256' },
        false,
        ['sign']
    );
    const kid = await computeJwkThumbprint(keyData.publicJwk);
    const iat = Math.floor(Date.now() / 1000);
    const nonce = crypto.getRandomValues(new Uint8Array(12));
    const nonceB64 = btoa(String.fromCharCode(...nonce))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

    const header = {
        alg: 'ES256',
        typ: 'JWT',
        kid
    };

    const payload = {
        iat,
        nonce: nonceB64
    };

    const unsigned = `${base64url(header)}.${base64url(payload)}`;

    const signature = await crypto.subtle.sign(
        { name: 'ECDSA', hash: { name: 'SHA-256' } },
        privateKey,
        new TextEncoder().encode(unsigned)
    );

    const sigB64 = btoa(String.fromCharCode(...new Uint8Array(signature)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

    return `${unsigned}.${sigB64}`;
};
