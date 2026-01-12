export async function ladeSpielUndCharakter(spielerId, charakterId) {
    const response = await fetch("http://localhost:8082/index.php/spiel", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({
            action: "ladeSpielUndCharakter",
            spieler_id: spielerId,
            charakter_id: charakterId
        })
    });

    try {
        return await response.json();
    } catch (e) {
        throw new Error("Backend hat kein JSON geliefert");
    }
}

export async function spielerAngriff(spielId, charakterId) {
    const response = await fetch("http://localhost:8082/index.php/spiel", {
        method: "POST",
        headers: { "Content-Type" : "application/json" },
        credentials: "include",
        body: JSON.stringify({
            action: "spielerAngriff",
            spiel_id: spielId,
            charakter_id: charakterId
        })
    });

    try {
        return await response.json();
    } catch(e) {
        throw new Error("Backend hat kein JSON geliefert");
    }
}

export async function nochmalSpielen(spielerId, charakterId, spielId) {
    const response = await fetch("http://localhost:8082/index.php/spiel", {
        method: "POST",
        headers: { "Content-Type" : "application/json" },
        credentials: "include",
        body: JSON.stringify({
            action: "nochmalSpielen",
            spieler_id: spielerId,
            charakter_id: charakterId,
            spiel_id: spielId
        })
    });

    try {
        return await response.json();
    } catch(e) {
        throw new Error("Backend hat kein JSON geliefert");
    }
}



export async function gegnerStatusSpeichern(spielId, gegnerListe) {
    const response = await fetch("http://localhost:8080/index.php/spiel", {
        method: "POST",
        headers: { "Content-Type" : "application/json" },
        credentials: "include",
        body: JSON.stringify({
            action: "speicherGegnerStatus",
            spiel_id: spielId,
            gegner_status: gegnerListe
        })
    });

    try {
        return await response.json();
    } catch(e) {
        throw new Error("Backend hat kein JSON geliefert");
    }
}