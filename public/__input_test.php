<?php
header('Content-Type: application/json');
echo json_encode(['raw' => file_get_contents('php://input'), 'ct' => $_SERVER['CONTENT_TYPE'] ?? 'none']);
