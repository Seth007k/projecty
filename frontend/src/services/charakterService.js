
export async function ladeCharaktere() {
    const response = await fetch("http://localhost:8080/charakter.php", {
        method: "GET",
        credentials: "include",
    });

    try {
        return await response.json();
    } catch(e) {
        throw new Error("Backend hat kein JSON geliefert.");
    }
}


export async function erstelleCharakter(name) {
    const response = await fetch("http://localhost:8080/charakter.php", {
        method: "POST",
        headers: { "Content-Type":"application/json"},
        credentials: "include",
        body: JSON.stringify({name}),
    });

    try {
        return await response.json();
    } catch (e) {
        throw new Error("Backen hat kein JSON geliefert.");
    }
}

export async function loescheCharakter(id) {
    const response = await fetch("http://localhost:8080/charakter.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json"},
        credentials: "include",
        body: JSON.stringify({id}),
    });

    try {
        return await response.json();
    } catch (e) {
        throw new Error("Backend hat kein JSON gelifert")
    }
}