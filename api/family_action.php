<?php
// api/family_action.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));
$user_id = getCurrentUserId();
$db = db();
$action = $_GET['action'] ?? 'get';

try {
    // 1. POBIERZ CZŁONKÓW RODZINY
    if ($action === 'get') {
        // Pobieramy tych, których JA zaprosiłem oraz tych, którzy zaprosili MNIE
        // Dla uproszczenia, pobieramy tylko "Moje zaproszenia" w tym demo
        $stmt = $db->prepare("
            SELECT f.id, f.invitee_email, f.status, f.created_at, u.username as member_name
            FROM family_links f
            LEFT JOIN users u ON f.member_id = u.id
            WHERE f.inviter_id = ?
        ");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // 2. WYŚLIJ ZAPROSZENIE
    elseif ($action === 'invite') {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);

        // Sprawdź czy to nie ja
        $me = $db->prepare("SELECT email FROM users WHERE id = ?");
        $me->execute([$user_id]);
        if($me->fetchColumn() === $email) throw new Exception("Nie możesz zaprosić samego siebie.");

        // Sprawdź czy taki user istnieje w systemie
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        $memberId = $targetUser ? $targetUser['id'] : null;
        $status = $targetUser ? 'accepted' : 'pending'; // W prawdziwym systemie wysyłamy email z linkiem akceptacyjnym. Tu dla uproszczenia: jeśli user istnieje, łączymy od razu (lub pending). Zróbmy pending.

        // Sprawdź duplikaty
        $dup = $db->prepare("SELECT id FROM family_links WHERE inviter_id = ? AND invitee_email = ?");
        $dup->execute([$user_id, $email]);
        if($dup->fetch()) throw new Exception("Już zaprosiłeś tę osobę.");

        $ins = $db->prepare("INSERT INTO family_links (inviter_id, invitee_email, member_id, status) VALUES (?, ?, ?, 'pending')");
        $ins->execute([$user_id, $email, $memberId]);

        echo json_encode(['success' => true]);
    }

    // 3. USUŃ POWIĄZANIE
    elseif ($action === 'delete') {
        $id = (int)$_GET['id'];
        $db->prepare("DELETE FROM family_links WHERE id = ? AND inviter_id = ?")->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>