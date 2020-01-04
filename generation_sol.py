from upemtk import *
from random import randint 
from operator import add

'''
for i in range (900) :
    
    longueur_segment = randint(25,50)
    diff_vertical = randint(1,20)
    if i % 2 == 0 :
        ligne(x_base,y_base, (x_base + longueur_segment),(y_base - diff_vertical))
        x_base += longueur_segment
        y_base -= diff_vertical
    else : 
        ligne(x_base,y_base, (x_base + longueur_segment),(y_base + diff_vertical))
        x_base += longueur_segment
        y_base += diff_vertical
    i += longueur_segment -1
'''	
def cree_terrain() :
    points_terrain = [(0, 700)]
    type_terrain = ['plat','colline','descendant','montant']
    for i in range (12) :
        x = randint(0,3) 
        terrain = type_terrain[x]
        if terrain == 'plat' :
            for i in range (5) :
                x = points_terrain[-1]
                x = tuple(map(add, x, (1,0)))
                points_terrain.append(x)
                
        elif terrain == 'colline' :
            for i in range (2) :
                x = points_terrain[-1]
                x += (1, -1)
                x = tuple(map(add, x, (1,-1)))
                points_terrain.append(x)
            x = points_terrain[-1]
            x += (1, 0)
            x = tuple(map(add, x, (1,0)))
            points_terrain.append(x)
            for i in range (2) :
                x = points_terrain[-1]
                x += (1, 1)
                x = tuple(map(add, x, (1,1)))
                points_terrain.append(x)
                
        elif terrain == 'descendant' :
            for i in range (5) :
                x = points_terrain[-1]
                x += (1, 1)
                x = tuple(map(add, x, (1,1)))
                points_terrain.append(x)
                
        elif terrain == 'montant' :
            for i in range (5) :
                x = points_terrain[-1]
                x += (1, -1)
                x = tuple(map(add, x, (1,-1)))
                points_terrain.append(x)
                    
    return points_terrain
