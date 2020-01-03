'''
Prototype Lunar Lander
'''

from upemtk import *
from math import *
from time import sleep

VITESSE_MAX = 6
VITESSE_ANG_MAX = 5

def affiche_fusee(position, angle):
    x0, y0 = position

    x1 = cos(radians(-angle) - atan(1/2)) * hypot(40, 20) / 2
    y1 = sin(radians(-angle) - atan(1/2)) * hypot(40, 20) / 2

    x2 = cos(radians(-angle) + atan(1/2)) * hypot(40, 20) / 2
    y2 = sin(radians(-angle) + atan(1/2)) * hypot(40, 20) / 2

    poly = [(x0+x1, y0+y1), (x0+x2, y0+y2), (x0-x1, y0-y1), (x0-x2, y0-y2)]
    
    polygone(poly, remplissage='light blue')

def affiche_sol():
    rectangle(0, 800, 900, 900, remplissage='grey')

def update_vitesse(fusee, vitesse, gravite, propulsion):
    vx, vy = vitesse
    ax, ay = gravite
    px, py = propulsion
    
    if check_on_ground(fusee, vitesse) and ay + py <= 0:
        return (0, 0)
    
    if hypot(vx + ax + px, vy + ay + py) <= VITESSE_MAX:
        return (vx + ax + px, vy + ay + py)
    else:
        return vitesse

def update_acceleration_angulaire(touche):
    if touche == 'Left':
        return 0.1
    elif touche == 'Right':
        return -0.1
    else:
        return 0

def update_vitesse_angulaire(vitesse_angulaire, acceleration):
    if acceleration != 0:
        if fabs(vitesse_angulaire + acceleration) > VITESSE_ANG_MAX:
            return vitesse_angulaire
        else:
            return vitesse_angulaire + acceleration

    if fabs(vitesse_angulaire) < 1:
        return 0
    elif vitesse_angulaire < 0:
        return vitesse_angulaire + 0.1
    else:
        return vitesse_angulaire - 0.1

def update_angle(angle, vitesse_angulaire):
    return angle + vitesse_angulaire

def move_fusee(fusee, vitesse):
    x0, y0 = fusee
    vx, vy = vitesse

    if not check_gnd_collision(fusee, vitesse):
        return (x0 + vx, y0 - vy)
    else:
        return (x0 + vx, 800 - 20)

def check_gnd_collision(fusee, vitesse):
    x0, y0 = fusee
    vx, vy = vitesse

    if y0 + 20 - vy > 800:
        return True
    return False

def check_on_ground(fusee, vitesse):
    x0, y0 = fusee
    vx, vy = vitesse
    
    if y0 + 21 >= 800:
        return True
    return False

def update_propulsion(touche, angle):
    if touche:
        return (0.1 * cos(radians(angle))), 0.1 * sin(radians(angle))
    return (0, 0)

if __name__ == '__main__':

    # Création de la fenêtre
    cree_fenetre(900, 900)

    # Initialisation des variables principales
    fusee_pos = (200, 0)    # Position de la fusee (x, y)
    fusee_angle = 90        # Angle en degrés de la fusée
    fusee_vit = (0, -1)     # Vecteur vitesse de la fusee (x, y)
    fusee_vit_angulaire = 0 # Vitesse angulaire de la fusée
    fusee_accel_angulaire = 0
    gravite = (0, -0.03)    # Vecteur accélération de la gravité (x, y)
    propulsion = (0, 0)     # Vecteur accélération de la propulsion (x, y)

    jouer = True
    
    # Boucle principale du jeu
    while jouer:
        # Affichages
        efface_tout()
        
        affiche_sol()
        affiche_fusee(fusee_pos, fusee_angle)

        mise_a_jour()

        # Gestion des évènements
        ev = donne_ev()
        ev_type = type_ev(ev)

        if ev_type == 'Quitte':
            jouer = False

        propulsion = update_propulsion(touche_pressee('g'), fusee_angle)

        if touche_pressee('Left') and touche_pressee('Right'):
            fusee_accel_angulaire = 0
        elif touche_pressee('Left'):
            fusee_accel_angulaire = update_acceleration_angulaire('Left')
        elif touche_pressee('Right'):
            fusee_accel_angulaire = update_acceleration_angulaire('Right')
        else:
            fusee_accel_angulaire = 0

        # Mécaniques
        fusee_vit_angulaire = update_vitesse_angulaire(fusee_vit_angulaire, fusee_accel_angulaire)
        fusee_angle = update_angle(fusee_angle, fusee_vit_angulaire)
        fusee_vit = update_vitesse(fusee_pos, fusee_vit, gravite, propulsion)
        fusee_pos = move_fusee(fusee_pos, fusee_vit)
        
        sleep(1/60)

    # Fermeture de la fenêtre
    ferme_fenetre()
