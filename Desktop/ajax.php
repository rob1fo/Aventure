<?php

session_start();

include_once "model/DB.php";

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

$r = array('success' => true);

if($_POST["action"] === "session"){
    if(isset($_SESSION["pseudo"]) && !empty($_SESSION["pseudo"])){
        $r['connecte'] = true;
        $r['pseudo'] = $_SESSION["pseudo"];
    }
    else{
        $r['connecte'] = false;
    }
}

elseif($_POST["action"] === "getpage"){
    $node = Node::getById($_POST["id"]);

    if ($node){

        $r['name'] = $node->getId();
        $r['content'] = $node->getContent();
        $r['author'] = $node->getAuthor();
        $r['links'] = array();
        $links = $node->getLinks();
        foreach($links as $link ){
            $r['links'][] = array(
                'text' => $link->getAction(),
                'destination' => $link->getTo(),
                'author' => $link->getAuthor(),
                'id' => $link->getId()
            );
        }
    }
    else{
        $r["erreurNotFound"] = true;
        $r['name'] = $_POST["id"];
    }
}

elseif($_POST["action"] === "connexion"){
    if(User::allowConnection($_POST["pseudo"],$_POST["password"])){
        $r['connecte'] = true;
        $r['pseudo'] = $_POST["pseudo"];
        $_SESSION["pseudo"] = $_POST["pseudo"];
    }
    else{
        $r['connecte'] = false;
    }
}

elseif($_POST["action"] === "deconnexion"){
    unset($_SESSION["pseudo"]);
    session_destroy();
}

elseif($_POST["action"] === "inscription"){
    if(!User::checkPseudo($_POST["pseudo"])){
        $r["erreurPseudoUsed"] = true;
    }

    if(!ctype_alnum($_POST["pseudo"])){
        $r["erreurAlphaNum"] = true;
    }

    if(strlen($_POST["pseudo"])<2){
        $r["erreurPseudoLength"] = true;
    }

    if(strlen($_POST["password"])<6){
        $r["erreurPassLength"] = true;
    }

    if($_POST["password"] !== $_POST["confirmation"]){
        $r["erreurConfirmation"] = true;
    }

    if(!(isset($r["erreurPseudoUsed"]) || isset($r["erreurAlphaNum"]) || isset($r["erreurPseudoLength"]) || isset($r["erreurPassLength"]) || isset($r["erreurConfirmation"]))){
        User::addUser($_POST["pseudo"],$_POST["password"]);
        $r['connecte'] = true;
        $r['pseudo'] = $_POST["pseudo"];
        $_SESSION["pseudo"] = $_POST["pseudo"];
    }

}

elseif($_POST["action"] === "edition"){
    if(isset($_SESSION["pseudo"]) && !empty($_SESSION["pseudo"])){
        $node = Node::getById($_POST["id"]);

        if(!$node){
            Node::addNode($_POST["id"],$_SESSION["pseudo"],$_POST["content"]);
        }
        else{
            if($node->getAuthor() === $_SESSION["pseudo"]){
                Node::modifyNode($_POST["id"],$_POST["content"]);
            }
            else{
                $r["erreurAuteur"] = true;
            }
        }
    }
    else{
        $r["erreurLogin"] = true;
    }
}

elseif($_POST["action"] === "link"){
    if(isset($_SESSION["pseudo"]) && !empty($_SESSION["pseudo"])){
        if(strlen($_POST["destination"])<1 || strlen($_POST["choix"])<1){
            $r["erreurLinkLength"] = true;
        }
        if(strlen($_POST["destination"])>32 || !ctype_alnum($_POST["destination"])){
            $r["erreurLinkID"] = true;
        }
        if(strlen($_POST["choix"])>64){
            $r["erreurLinkText"] = true;
        }
        if( !( isset($r["erreurLinkText"]) || isset($r["erreurLinkID"]) || isset($r["erreurLinkLength"]) ) ){
            if(Link::checkLinkId($_POST["id_link"])){
                Link::addLink($_POST["id"],$_POST["destination"],$_POST["choix"],$_SESSION["pseudo"]);
            }
            else{
                $link = Link::getLinkById($_POST["id_link"]);
                if($_SESSION["pseudo"] === $link->getAuthor()){
                    Link::editLink($_POST["id_link"],$_POST["destination"],$_POST["choix"]);
                }
                else{
                    $r["erreurAuteur"] = true;
                }
            }
        }
    }
    else{
        $r["erreurLogin"] = true;
    }
}

elseif($_POST["action"] === "deletelink"){
    if(isset($_SESSION["pseudo"]) && !empty($_SESSION["pseudo"])){
        if(Link::checkLinkId($_POST["id_link"])){
            $r["erreurLinkNotFound"] = true;
        }
        else{
            $link = Link::getLinkById($_POST["id_link"]);
            if($_SESSION["pseudo"] === $link->getAuthor()){
                Link::deleteLink($_POST["id_link"]);
            }
            else{
                $r["erreurAuteur"] = true;
            }
        }
    }
    else{
        $r["erreurLogin"] = true;
    }
}

elseif($_POST["action"] === "pageSuppression"){
    if(isset($_SESSION["pseudo"]) && !empty($_SESSION["pseudo"])){
        $node = Node::getById($_POST["id"]);

        if(!$node){

        }
        else{
            if($node->getAuthor() === $_SESSION["pseudo"]){
                Node::deletePage($_POST["id"]);
            }
            else{
                $r["erreurAuteur"] = true;
            }
        }

    }
    else{
        $r["erreurLogin"] = true;
    }
}

echo json_encode($r);