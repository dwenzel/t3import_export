Converter
=========

A converter transforms the incoming array into something that can be persisted, e.g. a Domain object, a stream or an array.
Custom converters can be used. 

Any converter must implement the *ConverterInterface*.

For configuration details see:
 * [ArrayToDomainObject](Converters/ArrayToDomainObject.md)
 * ArrayToXMLStream