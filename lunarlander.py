'''
Prototype Lunar Lander
'''

from upemtk import *
from math import *
from time import sleep

from generation_sol import cree_terrain

# Constantes
VITESSE_MAX = 6
VITESSE_ANG_MAX = 5
FRAMERATE = 30

def vers_polaire(vecteur):
    """Convertit coordonnées cartésiennes en coordonnées polaires""" 
    x, y = vecteur
    return (hypot(x, y), atan2(y, x))
    
def vers_cartes(vecteur):
    """Convertit coordonnées polaires en coordonnées cartésiennes""" 
    norme, angle = vecteur
    return (norme * cos(angle), norme * sin(angle))

def affiche_fusee(position, angle):
    """Affiche un rectangle représentant la fusée
    :param position: tuple, représentant la position x, y de la fusée
    :param angle: float, représentant l'angle actuel de la fusée
    """   
    polygone(get_coords_fusee(position, angle), remplissage='light blue')

def get_coords_fusee(position, angle):
    """Renvoie les coordonnées des 4 coins de la fusée
    :param position: tuple, coordonnées x, y de la fusée
    :param angle: float, angle de la fusée
    :return value: liste de tuples
    """
    x0, y0 = position
    
    x1 = cos(radians(-angle) - atan(1/2)) * hypot(40, 20) / 2
    y1 = sin(radians(-angle) - atan(1/2)) * hypot(40, 20) / 2

    x2 = cos(radians(-angle) + atan(1/2)) * hypot(40, 20) / 2
    y2 = sin(radians(-angle) + atan(1/2)) * hypot(40, 20) / 2

    return [(x0+x1, y0+y1), (x0+x2, y0+y2), (x0-x1, y0-y1), (x0-x2, y0-y2)]


def affiche_terrain(terrain):
    """Affiche le terrain"""
    polygone(terrain+[(1200, 800), (0, 800)], remplissage='grey')

def affiche_infos():
    """Affiche une barre en bas de l'écran affichant un certain nombre d'informations"""
    rectangle(0, 0, 1200, 100, remplissage='black')

def gen_terrain():
    """Génération d'un terrain plat
    :return list:
    """
    terrain = []
    for i in range(61):
        terrain.append((i*20, 700))
    return terrain

def update_propulsion(angle):
    """Met à jour le vecteur accélération de la propulsion principale de
    la fusée
    :param angle: float, angle de la fusée
    """
    return (0.2 * cos(radians(angle))), 0.2 * sin(radians(angle))

def update_vitesse(fusee, vitesse, gravite, propulsion):
    """Met à jour le vecteur vitesse de la fusée
    :param fusee: tuple, position x, y de la fusée
    :param vitesse: tuple, vecteur vitesse de la fusée
    :param gravité: tuple, vecteur accélération de la gravité
    :param propulsion: tuple, vecteur accélération de la propulsion
    :return: tuple, vecteur vitesse mise à jour
    """
    vx, vy = vitesse
    ax, ay = gravite
    px, py = propulsion
    
    if hypot(vx + ax + px, vy + ay + py) <= VITESSE_MAX:
        return (vx + ax + px, vy + ay + py)
    else:
        # Si la vitesse maximale est atteinte, on passe par les coordonnées
        # polaire pour correctement mettre à jour l'angle du vecteur tout en
        # fixant la norme à la vitesse maximale
        return vers_cartes((VITESSE_MAX, vers_polaire((vx + ax + px, vy + ay + py))[1]))

def update_acceleration_angulaire(touche):
    """Met à jour l'accélération angulaire selon la touche pressée
    :param touche: string, touche du clavier appuyée
    :return float: accélération angulaire
    """
    if touche == 'Left':
        return 0.2
    elif touche == 'Right':
        return -0.2
    else:
        return 0

def update_vitesse_angulaire(vitesse_angulaire, acceleration):
    """Met à jour la vitesse angulaire
    :param vitesse_angulaire: float
    :param acceleration: float, accélération angulaire
    :return float:"""
    
    # Si acceleration en cours, on accelere, sauf si la vitesse angulaire
    # maximale est déjà atteinte
    if acceleration != 0:
        if fabs(vitesse_angulaire + acceleration) > VITESSE_ANG_MAX:
            return vitesse_angulaire
        else:
            return vitesse_angulaire + acceleration

    # Si pas d'acceleration en cours, on décélère
    # si la vitesse est déjà assez basse, on arrête le mouvement rotatif
    if fabs(vitesse_angulaire) < 1:
        return 0
    elif vitesse_angulaire < 0:
        return vitesse_angulaire + 0.2
    else:
        return vitesse_angulaire - 0.2

def update_angle(angle, vitesse_angulaire):
    """Met à jour l'angle de la fusée
    :param angle: float
    :param vitesse_angulaire: float
    :return float:
    """
    return angle + vitesse_angulaire

def move_fusee(fusee, vitesse):
    """Met à jour la position de la fusée
    :param fusee: tuple, position x, y de la fusée
    :param vitesse: tuple, vecteur vitesse de la fusée
    :return tuple:
    """
    x0, y0 = fusee
    vx, vy = vitesse
    new_x, new_y = x0 + vx, y0 - vy

    # Empêche la fusée sort de l'écran
    if new_x <= 0:
        new_x = 0
    elif new_x >= 1200:
        new_x = 1200

    if new_y <= 100:
        new_y = 100
    
    return (new_x, new_y)

def direction(p1, p2, p3):
    """Evalue l'orientation du point p3 par rapport au segment (p1, p2)
    :param p1: tuple
    :param p2: tuple
    :param p3: tuple
    :return float:
    """
    return (p2[1] - p1[1]) * (p3[0] - p2[0]) - (p2[0] - p1[0]) * (p3[1] - p2[1])

def segments_croise(seg1, seg2):
    """Evalue si deux segments se croisent
    :param seg1: liste de 2 tuples, correspondant aux coordonnées des 2 points formant le segment 1
    :param seg2: liste de 2 tuples, correspondant aux coordonnées des 2 points formant le segment 2
    :return bool:
    """
    p1, p2 = seg1
    p3, p4 = seg2

    if direction(p1, p2, p3) * direction(p1, p2, p4) < 0:
        if direction(p3, p4, p1) * direction(p3, p4, p2) < 0:
            return True
    
    return False
    
def check_gnd_collision(fusee, angle, terrain):
    """Renvoie True en cas de collision entre la fusée et le terrain
    :param fusee: tuple, position x,y de la fusée
    :param angle: float, angle de la fusée
    :param terrain: liste de tuples
    :return bool:
    """
    x0, y0 = fusee

    # Recherche d'une collision avec le segment directement sous la fusée
    i = cherche_segment_plus_proche(x0)
    seg = (terrain[i], terrain[i+1])

    coords = get_coords_fusee(fusee, angle)

    if segments_croise((coords[0], coords[1]), seg):
        return True
    elif segments_croise((coords[1], coords[2]), seg):
        return True
    elif segments_croise((coords[2], coords[3]), seg):
        return True
    elif segments_croise((coords[3], coords[0]), seg):
        return True

    # Recherche d'une collision avec le segment à gauche
    if i > 0:
        seg = (terrain[i-1], terrain[i])
        if segments_croise((coords[0], coords[1]), seg):
            return True
        elif segments_croise((coords[1], coords[2]), seg):
            return True
        elif segments_croise((coords[2], coords[3]), seg):
            return True
        elif segments_croise((coords[3], coords[0]), seg):
            return True

    # Recherche d'une collision avec le segment à droite
    if i < 60:
        seg = (terrain[i+1], terrain[i+2])
        if segments_croise((coords[0], coords[1]), seg):
            return True
        elif segments_croise((coords[1], coords[2]), seg):
            return True
        elif segments_croise((coords[2], coords[3]), seg):
            return True
        elif segments_croise((coords[3], coords[0]), seg):
            return True

    return False

def cherche_segment_plus_proche(x):
    """Renvoie l'indice désignant le premier point du segment de terrain
    directement sous la fusée
    :param x: float, position x de la fusée
    :return int:
    """
    return int(x)//20

def check_victoire(position, angle, vitesse, terrain):
    """Renvoie True si l'atterrissage est correct
    :param position: tuple, position x, y de la fusée
    :param angle: float, angle de la fusée
    :param vitesse: tuple, vecteur vitesse de la fusée
    :param terrain: liste de tuples
    :return bool:
    """
    x, y = position
    vx, vy = vitesse
    
    i = cherche_segment_plus_proche(position[0])
    sol = (terrain[i], terrain[i+1])
    p1, p2 = sol
    x0, y0 = p1
    x1, y1 = p2

    # Evalue la position y du sol directement sous la fusée
    inter = fabs(x1-x)/fabs(x1-x0)*fabs(y0-y1)
    y_sol = min(y0, y1)-inter

    # Evalue si la fusée est bien sur le sol
    if y - y_sol < 25:
        # Evalue si le sol est suffisamment plat
        if fabs(degrees(atan(fabs(y0-y1)/fabs(x0-x1)))) <= 5:
            # Evalue si la fusée est suffisament verticale
            if fabs(angle-90) <= 5:
                # Evalue si la vitesse de la fusée est suffisament faible
                if hypot(vx, vy) < 2:
                    return True

    return False

if __name__ == '__main__':

    # Création de la fenêtre
    cree_fenetre(1200, 800)

    # Initialisation des variables principales
    fusee_pos = (600, 150)    # Position de la fusee (x, y)
    fusee_angle = 90        # Angle en degrés de la fusée
    fusee_vit = (0, -1)     # Vecteur vitesse de la fusee (x, y)
    fusee_vit_angulaire = 0 # Vitesse angulaire de la fusée
    fusee_accel_angulaire = 0 # Accélération angulaire de la fusée
    gravite = (0, -0.06)    # Vecteur accélération de la gravité (x, y)
    propulsion = (0, 0)     # Vecteur accélération de la propulsion (x, y)
    carburant = 10 * 30     # Quantité de carburant de la fusée
    terrain = gen_terrain() # Génération du terrain
    mode = 'A'
    
    jouer = True

    
    # Boucle principale du jeu
    while jouer:
        # Affichages

        efface_tout()
        
        affiche_infos()
        affiche_fusee(fusee_pos, fusee_angle)
        affiche_terrain(terrain)

        mise_a_jour()

        # Gestion des évènements
        ev = donne_ev()
        ev_type = type_ev(ev)

        if ev_type == 'Quitte':
            ferme_fenetre()
            break

        propulsion = (0, 0)
        if touche_pressee('g') and carburant > 0:
            propulsion = update_propulsion(fusee_angle)
            carburant -= 1

        if mode == 'A':
            if touche_pressee('Left') and touche_pressee('Right'):
                fusee_accel_angulaire = 0
            elif touche_pressee('Left'):
                fusee_accel_angulaire = update_acceleration_angulaire('Left')
            elif touche_pressee('Right'):
                fusee_accel_angulaire = update_acceleration_angulaire('Right')
            else:
                fusee_accel_angulaire = 0
        else:
            if touche_pressee('Left') and touche_pressee('Right'):
                fusee_accel_angulaire = 0
            elif touche_pressee('Left'):
                fusee_accel_angulaire = update_acceleration_angulaire('Left')
            elif touche_pressee('Right'):
                fusee_accel_angulaire = update_acceleration_angulaire('Right')
            else:
                fusee_accel_angulaire = 0

        # Mécaniques du jeu
        fusee_vit_angulaire = update_vitesse_angulaire(fusee_vit_angulaire, fusee_accel_angulaire)
        fusee_angle = update_angle(fusee_angle, fusee_vit_angulaire)
        fusee_vit = update_vitesse(fusee_pos, fusee_vit, gravite, propulsion)
        fusee_pos = move_fusee(fusee_pos, fusee_vit)

        if check_gnd_collision(fusee_pos, fusee_angle, terrain):
            jouer = False
        
        sleep(1/FRAMERATE)

    if check_victoire(fusee_pos, fusee_angle, fusee_vit, terrain):
        print('Victoire')
    else:
        print('Defeat')
    attend_ev()

    # Fermeture de la fenêtre
    ferme_fenetre()
