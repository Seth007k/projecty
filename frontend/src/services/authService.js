//API Logik

export async function login(benutzername, passwort) {
  const response = await fetch("http://localhost:8080/auth.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({
      action: "login",
      benutzername,
      passwort,
    }),
  });
  try {
    return await response.json();
  } catch (e) {
    throw new Error("Backendhatkeinjsongeliefert");
  }
}

export async function registrieren(benutzername, passwort) {
  const response = await fetch("http://localhost:8080/auth.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({
      action: "registrieren",
      benutzername,
      passwort,
    }),
  });

  return response.json();
}
