class Complex():
    """ A class that represents the complex number x+y*i """
    def __init__(self, x, y):
        """ x and y are numbers
            self is made up of a real number part (x)
            and an imaginary number part (y) """
        self._real = x
        self._imag = y

    def get_real(self):
        """ Returns the real part of self """
        return self._real

    def get_imag(self):
        """ Returns the imaginary part of self """
        return self._imag

    def __add__(self, oth):
        """ oth is an object of type Complex
            Returns a new Complex object whose 
            * real part is the sum of self and oth's real parts
            * imaginary part is the sum of self and oth's imaginary parts
        """
        new_real = self._real + oth.get_real()
        new_imag = self._imag + oth.get_imag()
        new_complex = Complex(new_real, new_imag)
        return new_complex

    def is_purely_real(self):
        """ Returns True if self's imaginary part is 0 """
        if self._imag == 0:
            return True
        else:
            return False

    def __str__(self):
        """ self's representation for a Complex object, whose real part is x 
            and imaginary part is y, is x+yi. If the real or imaginary parts 
            are 0, include the 0 in the representation. If the imaginary part 
            is negative, use x-yi as the representation.  """
        return_string = ''
        return_string = str(self._real)
        if self._imag >= 0:
            return_string += '+'
        return_string += str(self._imag)
        return_string += 'i'
        return return_string

# For example:

c1 = Complex(3,6)
c2 = Complex(9,7)
print(c1)             # prints 3+6i
print(c1.get_real())  # prints 3
print(c1.get_imag())  # prints 6

c5 = c1+c2
print(c5) # prints 12+13i

c3 = Complex(-5,0)
print(c3.is_purely_real())  # prints True
print(c3) # prints -5+0i

c4 = Complex(0,-2)
print(c4.is_purely_real())  # prints False
print(c4) # prints 0-2i

c4 = Complex(-5,2)
print(c4.is_purely_real())  # prints False
print(c4) # prints -5+2i

